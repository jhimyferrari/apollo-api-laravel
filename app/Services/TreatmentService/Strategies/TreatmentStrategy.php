<?php

namespace App\Services\TreatmentService\Strategies;

interface TreatmentStrategy
{
    public function handle(mixed $value): mixed;
}
