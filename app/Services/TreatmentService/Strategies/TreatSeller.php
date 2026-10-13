<?php

namespace App\Services\TreatmentService\Strategies;

use App\Actions\Validation\ValidateSeller;
use App\Models\Seller;

class TreatSeller implements TreatmentStrategy
{
    public function __construct(
        private readonly ValidateSeller $validateSeller,
    ) {}

    public function handle(mixed $value, string $field): Seller
    {
        return $this->validateSeller->execute($value, $field);

    }
}
