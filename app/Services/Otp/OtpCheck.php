<?php

namespace App\Services\Otp;

final class OtpCheck
{
    public const APPROVED = 'approved';

    public const WRONG = 'wrong';

    public const EXPIRED = 'expired';

    public const LOCKED = 'locked';

    public function __construct(
        public readonly string $status,
        public readonly ?int $attemptsLeft = null,
    ) {}

    public function approved(): bool
    {
        return $this->status === self::APPROVED;
    }
}
