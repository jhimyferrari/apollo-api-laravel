<?php

namespace App\Helpers\Test;

class PasswordHelper
{
    public static function generateValid(): string
    {

        $lower = fake()->regexify('[a-z]{2,4}');
        $upper = fake()->regexify('[A-Z]{2,4}');
        $numbers = fake()->regexify('[0-9]{2,4}');
        $special = fake()->regexify('[!@#$%^&*]{2,4}');

        return str_shuffle("{$lower}{$upper}{$numbers}{$special}");
    }
}
