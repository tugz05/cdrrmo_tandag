<?php

namespace App\Enums;

abstract class ReportTypeEnum {
    public const CALL = 'Call';
    public const MESSAGE = 'Message';

    public static function all()
    {
        return [
            self::CALL,
            self::MESSAGE,
        ];
    }
}