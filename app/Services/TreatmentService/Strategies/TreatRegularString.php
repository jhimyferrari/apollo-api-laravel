<?php

namespace App\Services\TreatmentService\Strategies;

use App\Exceptions\InvalidFieldException;

class TreatRegularString implements TreatmentStrategy
{
    public function handle(mixed $value): string
    {
        if (! \is_string($value)) {
            throw new InvalidFieldException("The value must be a string type, given $value");
        }
        $value = trim($value);

        return $value;
    }
}
