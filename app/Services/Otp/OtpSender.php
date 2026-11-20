<?php

namespace App\Services\Otp;

interface OtpSender
{
    public const MAX_ATTEMPTS = 5;

    public const EXPIRES_IN_SECONDS = 600;

    /**
     * @param  'sms'|'email'  $channel
     * @param  string  $destination  Normalized: E.164 phone or lower-case email
     */
    public function send(string $channel, string $destination): void;

    /**
     * @param  'sms'|'email'  $channel
     */
    public function check(string $channel, string $destination, string $code): OtpCheck;
}
