<?php

namespace App\Services\TreatmentService\Strategies;

use App\Actions\Validation\ValidateSupplier;
use App\Models\Supplier;

class TreatSupplier implements TreatmentStrategy
{
    public function __construct(
        private readonly ValidateSupplier $validateSupplier
    ) {}

    public function handle(mixed $value, string $field): Supplier
    {
        return $this->validateSupplier->execute($value, $field);

    }
}
