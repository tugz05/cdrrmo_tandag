<?php

namespace App\Support;

/**
 * Normalizes strings used as Twilio Voice "Client" identities across JWT, TwiML, and browser SDK.
 * Must match TwilioVoiceController so dial targets match Device.register() identities.
 */
final class TwilioClientIdentity
{
    /**
     * Twilio Client identity: alphanumeric + underscore only after normalization.
     */
    public static function sanitize(string $identity): string
    {
        $identity = trim($identity);

        if ($identity === '') {
            return 'guest';
        }

        if (str_starts_with(strtolower($identity), 'client:')) {
            $identity = trim(substr($identity, strlen('client:')));
        }

        $identity = preg_replace('/[^A-Za-z0-9_]/', '_', $identity) ?? 'guest';
        $identity = substr($identity, 0, 256);

        return $identity !== '' ? $identity : 'guest';
    }
}
