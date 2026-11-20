<?php

namespace App\Http\Controllers\API\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Otp\OtpCheck;
use App\Services\Otp\OtpSender;
use App\Support\MobileLoginPayload;
use App\Support\PhilippineMobileNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Passwordless sign-in and sign-up for the mobile app: a 6-digit code by SMS (Twilio Verify) or email.
 */
class OtpAuthController extends Controller
{
    private const RESEND_SECONDS = 60;

    private const REGISTRATION_SECONDS = 1800;

    /** Same wording as the password login, so both refuse a non-app account the same way. */
    private const NOT_A_MOBILE_ACCOUNT = 'This account is not enabled for the mobile app. Use an account with a valid role.';

    public function __construct(private OtpSender $otp) {}

    public function send(Request $request): JsonResponse
    {
        [$channel, $destination] = $this->destination($request);

        if ($wait = $this->sendLimit($request, $destination)) {
            return response()->json([
                'success' => false,
                'message' => 'Wait before asking for another code.',
                'data' => ['resend_in' => $wait],
            ], 429);
        }

        try {
            $this->otp->send($channel, $destination);
        } catch (Throwable $e) {
            Log::error('OTP send failed', [
                'channel' => $channel,
                'destination' => $this->masked($channel, $destination),
                'exception' => $e::class,
                'reason' => Str::limit($e->getMessage(), 200),
            ]);

            return response()->json([
                'success' => false,
                'message' => "We couldn't send the code. Try again in a minute.",
            ], 502);
        }

        // The same reply whether or not an account exists, so nobody can probe for registered numbers.
        return response()->json([
            'success' => true,
            'message' => 'We sent a code to '.$this->masked($channel, $destination).'.',
            'data' => [
                'channel' => $channel,
                'destination' => $destination,
                'expires_in' => OtpSender::EXPIRES_IN_SECONDS,
                'resend_in' => self::RESEND_SECONDS,
            ],
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        [$channel, $destination] = $this->destination($request);
        $code = (string) $request->validate(['code' => ['required', 'string', 'regex:/^\d{6}$/']])['code'];

        try {
            $check = $this->otp->check($channel, $destination, $code);
        } catch (Throwable $e) {
            Log::error('OTP check failed', [
                'channel' => $channel,
                'destination' => $this->masked($channel, $destination),
                'exception' => $e::class,
                'reason' => Str::limit($e->getMessage(), 200),
            ]);

            return response()->json([
                'success' => false,
                'message' => "We couldn't check the code. Try again in a minute.",
            ], 502);
        }

        if ($response = $this->rejectedCheck($check)) {
            return $response;
        }

        $user = $this->findAccount($channel, $destination);

        if ($user) {
            return $this->signIn($user, $channel, $destination);
        }

        $token = Str::random(64);
        Cache::put($this->registrationKey($token), [
            'channel' => $channel,
            'destination' => $destination,
        ], self::REGISTRATION_SECONDS);

        return response()->json([
            'success' => true,
            'is_new_user' => true,
            'message' => 'Code confirmed.',
            'data' => [
                'registration_token' => $token,
                'expires_in' => self::REGISTRATION_SECONDS,
                'channel' => $channel,
                'destination' => $destination,
            ],
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $key = $this->registrationKey((string) $request->input('registration_token', ''));

        if (! Cache::has($key)) {
            return $this->signUpExpired();
        }

        // Validated before the token is used up, so a typo doesn't force a new code.
        $validated = $request->validate([
            'registration_token' => ['required', 'string'],
            'fname' => ['required', 'string', 'max:100'],
            'lname' => ['required', 'string', 'max:100'],
            'birthdate' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', 'after:1900-01-01'],
            'address' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $pending = Cache::pull($key);
        if (! is_array($pending)) {
            return $this->signUpExpired();
        }

        ['channel' => $channel, 'destination' => $destination] = $pending;

        if ($existing = $this->findAccount($channel, $destination)) {
            return $this->signIn($existing, $channel, $destination);
        }

        $user = DB::transaction(function () use ($validated, $channel, $destination) {
            $user = new User([
                'name' => trim($validated['fname'].' '.$validated['lname']),
                'fname' => $validated['fname'],
                'lname' => $validated['lname'],
                'birthdate' => $validated['birthdate'],
                'address' => $validated['address'],
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'address_updated_at' => now(),
                // The column isn't nullable; this account signs in by code, never by password.
                'password' => Hash::make(Str::random(40)),
            ]);

            if ($channel === 'sms') {
                $user->phone = $destination;
                $user->phone_verified_at = now();
            } else {
                $user->email = $destination;
                $user->forceFill(['email_verified_at' => now()]);
            }

            $user->save();
            $user->addRole('user');
            $user->syncAppRoleFromRoles();

            return $user;
        });

        return response()->json(
            ['is_new_user' => false] + MobileLoginPayload::mobileAuthJson(
                $user->fresh(),
                $user->createToken('user-token')->plainTextToken,
                'Account created.',
            ),
            201,
        );
    }

    /**
     * @return array{0: 'sms'|'email', 1: string}
     */
    private function destination(Request $request): array
    {
        $validated = $request->validate([
            'channel' => ['required', Rule::in(['sms', 'email'])],
            'destination' => ['required', 'string', 'max:255'],
        ]);

        if ($validated['channel'] === 'email') {
            $email = Str::lower(trim($validated['destination']));
            validator(['destination' => $email], ['destination' => ['email:rfc']])->validate();

            return ['email', $email];
        }

        $phone = PhilippineMobileNumber::normalize($validated['destination']);

        if ($phone === null) {
            throw ValidationException::withMessages([
                'destination' => 'Enter a Philippine mobile number, for example 0917 123 4567.',
            ]);
        }

        return ['sms', $phone];
    }

    /**
     * @return int|null Seconds to wait, or null when a code may be sent now
     */
    private function sendLimit(Request $request, string $destination): ?int
    {
        $hash = hash('sha256', $destination);
        $limits = [
            ["otp-send-minute:{$hash}", 1, 60],
            ["otp-send-hour:{$hash}", 5, 3600],
            ['otp-send-ip:'.$request->ip(), 20, 3600],
        ];

        foreach ($limits as [$key, $max]) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                return max(1, RateLimiter::availableIn($key));
            }
        }

        // Counted before sending, so a failing send can't be retried in a tight loop.
        foreach ($limits as [$key, , $decay]) {
            RateLimiter::hit($key, $decay);
        }

        return null;
    }

    private function rejectedCheck(OtpCheck $check): ?JsonResponse
    {
        return match ($check->status) {
            OtpCheck::APPROVED => null,
            OtpCheck::WRONG => response()->json([
                'success' => false,
                'message' => "That code isn't right. Check it and try again.",
                'data' => ['attempts_left' => $check->attemptsLeft],
            ], 422),
            OtpCheck::LOCKED => response()->json([
                'success' => false,
                'message' => 'Too many wrong codes. Send a new one.',
                'data' => ['attempts_left' => 0],
            ], 429),
            default => response()->json([
                'success' => false,
                'message' => 'This code has expired. Send a new one.',
            ], 410),
        };
    }

    /**
     * Includes soft-deleted accounts, so a deleted account is refused instead of duplicated.
     * A phone or email only counts once verified, or if it was on the account before code sign-in
     * existed: otherwise anyone could type a stranger's into their profile and receive their sign-in.
     * Phone numbers aren't unique: a verified number wins, then the oldest account.
     */
    private function findAccount(string $channel, string $destination): ?User
    {
        $query = User::withTrashed();

        if ($channel === 'email') {
            return $query
                ->where(fn ($trusted) => $trusted->whereNotNull('email_verified_at')->orWhere('legacy_email_sign_in', true))
                ->whereRaw('LOWER(email) = ?', [$destination])
                ->orderBy('id')
                ->first();
        }

        $variants = PhilippineMobileNumber::storedVariants($destination);
        $matches = $query
            ->where(fn ($trusted) => $trusted->whereNotNull('phone_verified_at')->orWhere('legacy_phone_sign_in', true))
            ->whereRaw(
                "REPLACE(REPLACE(phone, ' ', ''), '-', '') IN (".implode(', ', array_fill(0, count($variants), '?')).')',
                $variants,
            )
            ->orderByRaw('CASE WHEN phone_verified_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('id')
            ->get();

        if ($matches->count() > 1) {
            Log::warning('OTP sign-in: phone number matches several accounts', [
                'destination' => PhilippineMobileNumber::masked($destination),
                'user_ids' => $matches->pluck('id')->all(),
                'chosen' => $matches->first()->id,
            ]);
        }

        return $matches->first();
    }

    private function signIn(User $user, string $channel, string $destination): JsonResponse
    {
        if ($user->trashed() || ! $user->canAccessMobileApp()) {
            return response()->json([
                'success' => false,
                'message' => self::NOT_A_MOBILE_ACCOUNT,
                'data' => [],
            ], 403);
        }

        if ($channel === 'sms') {
            $user->phone = $destination;
            $user->phone_verified_at ??= now();
        } elseif ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()]);
        }

        $user->save();
        $user->syncAppRoleFromRoles();

        return response()->json(
            ['is_new_user' => false] + MobileLoginPayload::mobileAuthJson(
                $user->fresh(),
                $user->createToken('user-token')->plainTextToken,
            ),
        );
    }

    private function signUpExpired(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Your sign-up took too long. Request a new code.',
        ], 410);
    }

    private function registrationKey(string $token): string
    {
        return 'otp-reg:'.hash('sha256', $token);
    }

    private function masked(string $channel, string $destination): string
    {
        if ($channel === 'sms') {
            return PhilippineMobileNumber::masked($destination);
        }

        [$local, $domain] = explode('@', $destination, 2) + [1 => ''];

        return Str::substr($local, 0, 1).'•••@'.$domain;
    }
}
