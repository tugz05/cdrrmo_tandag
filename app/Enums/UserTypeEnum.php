<?php

namespace App\Enums;

abstract class UserTypeEnum
{
    public const ADMIN = 'admin';

    public const SUPER_ADMIN = 'super_admin';

    /** Citizen / public mobile app accounts (Laratrust role name). */
    public const USER = 'user';

    /** Staff / rescuer mobile app accounts (Laratrust role name). */
    public const STAFF = 'staff';

    /**
     * Roles that may sign in to the Flutter mobile app (not web dashboard admins).
     *
     * @return list<string>
     */
    public static function mobileAppRoleNames(): array
    {
        return [
            self::USER,
            self::STAFF,
        ];
    }

    public static function all(): array
    {
        return [
            self::ADMIN,
            self::SUPER_ADMIN,
            self::USER,
            self::STAFF,
        ];
    }
}
