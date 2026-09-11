<?php

namespace App\Services\TreatmentService\Strategies;

use App\ValueObjects\Money;

/**
 * Action for validate and formate documents
 */
class TreatMoney implements TreatmentStrategy
{
    public function handle(mixed $value): Money
    {

        return Money::fromDecimal($value);
    }
}
