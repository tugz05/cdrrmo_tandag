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
     * Roles that may sign in to the Flutter mobile app (Laratrust `roles` table).
     * Admin / super_admin use the app as staff/rescuer; `user` = citizen; `staff` = field staff.
     *
     * @return list<string>
     */
    public static function mobileAppRoleNames(): array
    {
        return [
            self::USER,
            self::STAFF,
            self::ADMIN,
            self::SUPER_ADMIN,
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
