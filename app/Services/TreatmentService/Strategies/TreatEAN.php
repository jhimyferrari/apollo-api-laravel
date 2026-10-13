<?php

namespace App\Services\TreatmentService\Strategies;

use App\Actions\Validation\ValidateEAN;

class TreatEAN implements TreatmentStrategy
{
    public function __construct(
        private readonly ValidateEAN $validateEAN,
    ) {}

    public function handle(mixed $value, string $field): string
    {

        return $this->validateEAN->execute($value, $field);

    }
}
