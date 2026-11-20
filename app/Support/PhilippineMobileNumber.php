<?php

namespace App\Support;

final class PhilippineMobileNumber
{
    /**
     * +639XXXXXXXXX from 09…, 9…, 639… or +63…, with spaces or dashes. Null for anything else, landlines included.
     */
    public static function normalize(?string $input): ?string
    {
        $digits = preg_replace('/[\s-]/', '', trim((string) $input));

        if (! preg_match('/^(?:\+63|63|0)?(9\d{9})$/', $digits, $matches)) {
            return null;
        }

        return '+63'.$matches[1];
    }

    /**
     * Formats an E.164 number may be stored in by older accounts.
     *
     * @return list<string>
     */
    public static function storedVariants(string $e164): array
    {
        $local = substr($e164, 3);

        return [$e164, '63'.$local, '0'.$local, $local];
    }

    public static function masked(string $e164): string
    {
        return '+63 '.substr($e164, 3, 3).' ••• '.substr($e164, -4);
    }
}
