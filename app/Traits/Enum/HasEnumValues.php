<?php

namespace App\Traits\Enum;

trait HasEnumValues
{
    public static function allValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
