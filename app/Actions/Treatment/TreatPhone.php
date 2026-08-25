<?php

namespace App\Actions\Treatment;

class TreatPhone
{
    public function execute($value): string
    {
        $value = trim($value);

        return $value;
    }
}
