<?php

namespace App\Services\TreatmentService\Strategies;

use App\Actions\Validation\ValidateBrand;
use App\Models\Brand;

class TreatBrand implements TreatmentStrategy
{
    public function __construct(
        private readonly ValidateBrand $validateBrand
    ) {}

    public function handle(mixed $value, string $field): Brand
    {
        return $this->validateBrand->execute($value, $field);
    }
}
