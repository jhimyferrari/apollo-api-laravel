<?php

namespace App\Services\TreatmentService\Strategies;

use App\Actions\Validation\Address\ValidateCep;

class TreatCEP implements TreatmentStrategy
{
    public function __construct(
        private readonly ValidateCep $validateCep
    ) {}

    public function handle(mixed $value): string
    {
        return $this->validateCep->execute($value);

    }
}
