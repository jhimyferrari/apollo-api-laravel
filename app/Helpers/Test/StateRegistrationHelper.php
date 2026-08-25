<?php

namespace App\Helpers\Test;

class StateRegistrationHelper
{
    public static function generateIE(): string
    {
        return fake()->regexify('[0-9]{9,12}');
    }
}
