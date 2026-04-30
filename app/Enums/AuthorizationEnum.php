<?php

namespace App\Enums;

abstract class AuthorizationEnum
{
    public const SUPER_ADMIN = "super_admin";
    public const ADMIN = "admin";

    public static function adminRoles(): array  
    {
        return [
            self::ADMIN,
            self::SUPER_ADMIN
        ];
    }
}
