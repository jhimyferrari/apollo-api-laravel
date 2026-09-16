<?php

namespace App\Services\TreatmentService\Strategies;

class TreatPhone implements TreatmentStrategy
{
    public function handle($value): string
    {
        $value = trim($value);

        return $value;
    }
}
