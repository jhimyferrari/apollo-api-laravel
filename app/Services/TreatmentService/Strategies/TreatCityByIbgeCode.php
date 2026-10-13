<?php

namespace App\Services\TreatmentService\Strategies;

use App\Actions\Validation\Address\ValidateCity;
use App\Models\City;

class TreatCityByIbgeCode implements TreatmentStrategy
{
    public function __construct(
        private ValidateCity $validateCity,
    ) {}

    public function handle(mixed $value, string $field): City
    {
        return $this->validateCity->execute($value, $field);
    }
}
