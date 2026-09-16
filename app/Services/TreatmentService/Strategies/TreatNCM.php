<?php

namespace App\Services\TreatmentService\Strategies;

use App\Actions\Validation\ValidateNCM;
use App\Models\NcmCode;

class TreatNCM implements TreatmentStrategy
{
    public function __construct(
        private ValidateNCM $validateNcm,
    ) {}

    public function handle(mixed $value): NcmCode
    {
        return $this->validateNcm->execute($value);

    }
}
