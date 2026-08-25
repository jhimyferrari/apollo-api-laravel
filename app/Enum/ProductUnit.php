<?php

namespace App\Enum;

use App\Traits\Enum\HasEnumValues;

enum ProductUnit: string
{
    use HasEnumValues;
    case Unit = 'un';
    case Kilogram = 'kg';
    case Box = 'cx';
    case Liter = 'l';
    case Meter = 'm';

    public static function random(): string
    {
        $values = self::allValues();

        return $values[random_int(0, (\count($values) - 1))];

    }
}
