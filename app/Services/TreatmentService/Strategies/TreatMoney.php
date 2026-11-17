<?php

namespace App\Services\TreatmentService\Strategies;

use App\Actions\Validation\Money\ValidateIsGreaterThan;
use App\ValueObjects\Money;

/**
 * Action for validate and formate documents
 */
class TreatMoney implements TreatmentStrategy
{
    public function __construct(
        private readonly ValidateIsGreaterThan $validateIsGreaterThan,
        private readonly ?Money $mustBeGreaterThan = null,
        private readonly ?string $fieldMustBeGreaterThan = null,
    ) {}

    public function mustBeGreaterThan(Money $value, string $fieldName): self
    {
        return new self(
            validateIsGreaterThan: $this->validateIsGreaterThan,
            mustBeGreaterThan: $value,
            fieldMustBeGreaterThan: $fieldName,
        );
    }

    public function handle(mixed $value, string $field): Money
    {
        $money = Money::fromDecimal($value);

        if ($this->mustBeGreaterThan !== null && $this->fieldMustBeGreaterThan !== null) {
            $this->validateIsGreaterThan->execute($money, $this->mustBeGreaterThan, $field, $this->fieldMustBeGreaterThan);
        }

        return $money;
    }
}
