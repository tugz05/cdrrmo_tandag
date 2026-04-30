<?php 

namespace App\Services;
use App\Enums\JVerficationStatus;
use App\Enums\JVerificationType;
use App\Mail\VerificationCodeSent;
use App\Models\Code;
use App\Models\User;
use App\Traits\JTrait;
use Carbon\Carbon;
use App\Enums\JVerificationCodeStatus;
use Illuminate\Support\Facades\Mail;

class VerificationCodeService {
    use JTrait;

    private string $type;
    private const LIMIT_TIME_IN_MINUTES = 5;
    private array $selectedColumns = [
        'id',
        'phone_verified_at',
        'email_verified_at',
        'updated_at'
    ];

    public function __construct(private $user)
    {}

    public function type(string $type)
    {
        $this->type = $type;
        return $this;
    }

    public function send()
    {
        $code = $this->generateVerificationCode();

        Code::create([
            'user_id' => $this->user->id,
            'code' => $code
        ]);

        info($code);

        return $this->type == JVerificationType::EMAIL 
            ? $this->sendToEmail($code)
            : $this->sendToPhone($code);
    }

    public function verify(string $codeToVerify): string
    {
        $latestCode = Code::whereUserId($this->user->id)
            ->latest()
            ->first();

        return $this->getStatus($latestCode, $codeToVerify);
    }

    private function getStatus($latestCode, $codeToVerify): string
    {
        if ($latestCode && $codeToVerify == $latestCode->code) {
            $createdAt = $latestCode->created_at;

            if ($this->isExpiredCode($createdAt))
                return JVerificationCodeStatus::EXPIRED;

            switch($this->type) {
                case JVerificationType::EMAIL:
                    $this->verifiedEmail();
                    break;
                case JVerificationType::PHONE:
                    $this->verifiedPhone();
                    break;
                default:
            }

            return JVerificationCodeStatus::VERIFIED;
        }

        return JVerificationCodeStatus::INVALID;
    }

    private function isExpiredCode($createdAt)
    {
        $now = Carbon::now();
        return ($createdAt->diffInMinutes($now) > self::LIMIT_TIME_IN_MINUTES);
    }

    private function sendToEmail(string $code)
    {
        return Mail::to($this->user->email)
            ->send(new VerificationCodeSent($code));
    }

    private function sendToPhone($code)
    {
        return;
    }

    private function verifiedForgotPassword()
    {

    }

    private function verifiedEmail()
    {
        return $this->verified('email_verified_at');
    }

    private function verifiedPhone()
    {
        return $this->verified('phone_verified_at');
    }

    private function verified($column)
    {
        try {
            if (is_null($this->user->$column)) {
                $this->user->update([
                    $column => Carbon::today()
                ]);
            }
        } catch (\Exception $exception) {}
    }
}