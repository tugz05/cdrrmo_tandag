<?php

namespace App\Enums;

abstract class JVerificationCodeStatus {
    public const EXPIRED = 'expired';
    public const INVALID = 'invalid';
    public const VERIFIED = 'verified';
}