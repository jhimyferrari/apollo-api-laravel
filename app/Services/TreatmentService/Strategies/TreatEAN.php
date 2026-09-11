<?php

namespace App\Services\TreatmentService\Strategies;

use App\Actions\Validation\ValidateEAN;
use App\Exceptions\InvalidFieldException;

class TreatEAN implements TreatmentStrategy
{
    public function __construct(
        private readonly ValidateEAN $validateEAN,
    ) {}

    public function handle(mixed $value): string
    {

        if (! \is_string($value)) {
            throw new InvalidFieldException("The value must be a string type, given $value");
        }
        $value = $this->validateEAN->execute($value);

        return $value;
    }
}
