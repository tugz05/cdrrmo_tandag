<?php

namespace App\Enums;

abstract class PostTypeEnum
{
    public const NEWS = 'News';
    public const SAFETY_TIPS = 'Safety Tips';
    public const EMERGENCY_PREPAREDNESS = 'Emergency Preparedness';

    public static function all()
    {
        return [
            self::NEWS,
            self::SAFETY_TIPS,
            self::EMERGENCY_PREPAREDNESS,
        ];
    }
}
