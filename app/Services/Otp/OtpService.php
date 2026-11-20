<?php

namespace App\Services\Otp;

use App\Mail\OtpCodeMail;
use App\Models\OtpCode;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Twilio\Exceptions\RestException;
use Twilio\Rest\Client;

/**
 * SMS codes come from Twilio Verify, which generates, delivers and expires them.
 * Email codes are made here, and only their hash is stored.
 */
class OtpService implements OtpSender
{
    /** Twilio Verify: no pending verification (expired, used, or never sent). */
    private const TWILIO_NOT_FOUND = 20404;

    /** Twilio Verify: max check attempts reached. */
    private const TWILIO_MAX_ATTEMPTS = 60202;

    public function send(string $channel, string $destination): void
    {
        if ($channel === 'sms') {
            $this->verifyService()->verifications->create($destination, 'sms');
            Cache::forget($this->smsAttemptsKey($destination));

            return;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpCode::query()
            ->where('destination', $destination)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        OtpCode::query()->create([
            'channel' => 'email',
            'destination' => $destination,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addSeconds(self::EXPIRES_IN_SECONDS),
        ]);

        Mail::to($destination)->queue(new OtpCodeMail($code));
    }

    public function check(string $channel, string $destination, string $code): OtpCheck
    {
        return $channel === 'sms'
            ? $this->checkSms($destination, $code)
            : $this->checkEmail($destination, $code);
    }

    private function checkEmail(string $destination, string $code): OtpCheck
    {
        $otp = OtpCode::query()
            ->where('destination', $destination)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (! $otp || $otp->expires_at->isPast()) {
            return new OtpCheck(OtpCheck::EXPIRED);
        }

        if (Hash::check($code, $otp->code_hash)) {
            $otp->update(['consumed_at' => now()]);

            return new OtpCheck(OtpCheck::APPROVED);
        }

        $otp->increment('attempts');

        if ($otp->attempts >= self::MAX_ATTEMPTS) {
            $otp->update(['consumed_at' => now()]);

            return new OtpCheck(OtpCheck::LOCKED, 0);
        }

        return new OtpCheck(OtpCheck::WRONG, self::MAX_ATTEMPTS - $otp->attempts);
    }

    /**
     * Twilio doesn't say how many tries are left, so they're counted here per sent code.
     */
    private function checkSms(string $destination, string $code): OtpCheck
    {
        try {
            $status = $this->verifyService()->verificationChecks->create([
                'to' => $destination,
                'code' => $code,
            ])->status;
        } catch (RestException $e) {
            return match ($e->getCode()) {
                self::TWILIO_NOT_FOUND => new OtpCheck(OtpCheck::EXPIRED),
                self::TWILIO_MAX_ATTEMPTS => new OtpCheck(OtpCheck::LOCKED, 0),
                default => throw $e,
            };
        }

        if ($status === 'approved') {
            Cache::forget($this->smsAttemptsKey($destination));

            return new OtpCheck(OtpCheck::APPROVED);
        }

        $key = $this->smsAttemptsKey($destination);
        Cache::add($key, 0, self::EXPIRES_IN_SECONDS);
        $attempts = (int) Cache::increment($key);

        return $attempts >= self::MAX_ATTEMPTS
            ? new OtpCheck(OtpCheck::LOCKED, 0)
            : new OtpCheck(OtpCheck::WRONG, self::MAX_ATTEMPTS - $attempts);
    }

    private function verifyService(): \Twilio\Rest\Verify\V2\ServiceContext
    {
        $serviceSid = trim((string) config('services.twilio.verify_service_sid'));

        if ($serviceSid === '') {
            throw new RuntimeException('TWILIO_VERIFY_SERVICE_SID is not set.');
        }

        $client = new Client(config('services.twilio.sid'), config('services.twilio.auth_token'));

        return $client->verify->v2->services($serviceSid);
    }

    private function smsAttemptsKey(string $destination): string
    {
        return 'otp-sms-attempts:'.hash('sha256', $destination);
    }
}
