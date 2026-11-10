<?php

namespace App\Services\TreatmentService\Strategies;

use App\Actions\Validation\ValidateProduct;
use App\Actions\Validation\ValidateResourceStatus;
use App\Models\Product;

class TreatProduct implements TreatmentStrategy
{
    public function __construct(
        private readonly ValidateProduct $validateProduct,
        private readonly ValidateResourceStatus $validateResourceStatus,
        private readonly bool $allowInactive = false,
    ) {}

    public function allowInactive(bool $allow = true): self
    {
        return new self($this->validateProduct, $this->validateResourceStatus, $allow);
    }

    public function handle(mixed $value, string $field): Product
    {
        $product = $this->validateProduct->execute($value, $field);

        if (! $this->allowInactive) {
            $this->validateResourceStatus->execute($product, $field);
        }

        return $product;
    }
}
