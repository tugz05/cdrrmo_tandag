<?php

namespace App\Support;

use App\Enums\AppMobileRole;
use App\Helpers\JHelper;
use App\Models\User;
use DateTimeInterface;

/**
 * Success payload for POST /api/v1/auth/login and POST /api/v1/auth/google (mobile).
 * Coerces nullables so JSON never sends null for keys the Flutter client maps to non-nullable String.
 */
final class MobileLoginPayload
{
    public static function accountStatus(User $user): string
    {
        if (! is_null($user->confirmed_at)) {
            return 'verified';
        }

        if (count(JHelper::getValidImages($user->id)) > 0) {
            return 'pending_verification';
        }

        return 'for_verification';
    }

    /**
     * Full JSON body for successful mobile password / Google auth.
     * Top-level `app_role` and booleans are for Flutter switches without digging only into `data`.
     *
     * @return array{success: true, message: string, app_role: string, is_staff: bool, is_citizen: bool, data: array<string, mixed>}
     */
    public static function mobileAuthJson(User $user, string $plainTextToken, string $message = 'Login successfully.'): array
    {
        $data = self::data($user, $plainTextToken);
        $appRole = $data['app_role'];

        return [
            'success' => true,
            'message' => $message,
            'app_role' => $appRole,
            'is_staff' => $appRole === AppMobileRole::Staff->value,
            'is_citizen' => $appRole === AppMobileRole::Citizen->value,
            'data' => $data,
        ];
    }

    /**
     * @return array{
     *     app_role: string,
     *     id: int,
     *     name: string,
     *     email: string,
     *     phone: string,
     *     address: string,
     *     email_verified_at: string,
     *     confirmed_at: string,
     *     status: string,
     *     token: string
     * }
     */
    public static function data(User $user, string $plainTextToken): array
    {
        $appRole = $user->mobileApiAppRole()->value;

        return [
            'app_role' => $appRole,
            'id' => (int) $user->id,
            'name' => (string) ($user->name ?? ''),
            'email' => (string) ($user->email ?? ''),
            'phone' => (string) ($user->phone ?? ''),
            'address' => (string) ($user->address ?? ''),
            'email_verified_at' => self::dateOrEmpty($user->email_verified_at),
            'confirmed_at' => self::dateOrEmpty($user->confirmed_at),
            'status' => self::accountStatus($user),
            'token' => $plainTextToken,
        ];
    }

    private static function dateOrEmpty(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }

        return (string) $value;
    }
}
