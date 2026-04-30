<?php
namespace App\Enums;

abstract class UserTypeEnum {
    public const ADMIN = 'admin';
    public const SUPER_ADMIN = 'super_admin';

    public static function all()
    {
        return [
            self::ADMIN,
            self::SUPER_ADMIN
        ];
    }
}