<?php

namespace App\Enums;

abstract class ReportStatusEnum {
    public const PENDING = 'Pending';
    public const IN_PROGRESS = 'In Progress';
    public const RESCUED = 'Rescued';

    public static function all()
    {
        return [
            self::PENDING,
            self::IN_PROGRESS,
            self::RESCUED,
        ];
    }
}