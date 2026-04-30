<?php

namespace App\Enums;

abstract class JHelper
{
    public static function getRandomValue(array $values = [])
    {
        $randomKey = array_rand($values);
        $randomValue = $values[$randomKey];

        return $randomValue;
    }
    
    public static function truncateText($text, $maxLength) 
    {
        return strlen($text) > $maxLength
            ? substr($text, 0, $maxLength) . "..."
            : $text;
    }
}
