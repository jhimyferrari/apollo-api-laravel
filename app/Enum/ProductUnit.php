<?php

namespace App\Enum;

enum ProductUnit: string
{
    case Unit = 'un';
    case Kilogram = 'kg';
    case Box = 'cx';
    case Liter = 'l';
    case Meter = 'm';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function random(): string
    {
        $values = self::values();

        return $values[random_int(0, (\count($values) - 1))];

    }
}
