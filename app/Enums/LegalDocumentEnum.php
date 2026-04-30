<?php

namespace App\Enums;

abstract class LegalDocumentEnum
{
    public const ABOUT = 'about';
    public const TERMS = 'terms';
    public const PRIVACY = 'privacy';

    public static function getValues(): array
    {
        return [
            self::ABOUT,
            self::TERMS,
            self::PRIVACY
        ];
    }
}
