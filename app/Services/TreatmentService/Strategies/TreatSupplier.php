<?php

namespace App\Services\TreatmentService\Strategies;

use App\Actions\Validation\ValidateResourceStatus;
use App\Actions\Validation\ValidateSupplier;
use App\Models\Supplier;

class TreatSupplier implements TreatmentStrategy
{
    public function __construct(
        private readonly ValidateSupplier $validateSupplier,
        private readonly ValidateResourceStatus $validateResourceStatus,
        private readonly bool $allowInactive = false,
    ) {}

    public function allowInactive(bool $allow = true): self
    {
        return new self($this->validateSupplier, $this->validateResourceStatus, $allow);
    }

    public function handle(mixed $value, string $field): Supplier
    {
        $supplier = $this->validateSupplier->execute($value, $field);

        if (! $this->allowInactive) {
            $this->validateResourceStatus->execute($supplier, $field);
        }

        return $supplier;
    }
}
