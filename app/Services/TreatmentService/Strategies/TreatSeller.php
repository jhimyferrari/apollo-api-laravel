<?php

namespace App\Services\TreatmentService\Strategies;

use App\Actions\Validation\ValidateResourceStatus;
use App\Actions\Validation\ValidateSeller;
use App\Models\Seller;

class TreatSeller implements TreatmentStrategy
{
    public function __construct(
        private readonly ValidateSeller $validateSeller,
        private readonly ValidateResourceStatus $validateResourceStatus,
        private readonly bool $allowInactive = false,
    ) {}

    public function allowInactive(bool $allow = true): self
    {
        return new self($this->validateSeller, $this->validateResourceStatus, $allow);
    }

    public function handle(mixed $value, string $field): Seller
    {
        $seller = $this->validateSeller->execute($value, $field);

        if (! $this->allowInactive) {
            $this->validateResourceStatus->execute($seller, $field);
        }

        return $seller;
    }
}
