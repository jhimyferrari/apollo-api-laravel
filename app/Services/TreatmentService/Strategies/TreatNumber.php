<?php

namespace App\Services\TreatmentService\Strategies;

use App\Exceptions\InvalidFieldException;

final class TreatNumber implements TreatmentStrategy
{
    public function __construct(
        private readonly bool $mustBeInteger = false,
        private readonly ?bool $mustBePositive = null,
    ) {}

    public function asInteger(): self
    {
        return new self(mustBeInteger: true, mustBePositive: $this->mustBePositive);
    }

    public function positive(): self
    {
        return new self(mustBeInteger: $this->mustBeInteger, mustBePositive: true);
    }

    public function negative(): self
    {
        return new self(mustBeInteger: $this->mustBeInteger, mustBePositive: false);
    }

    public function handle(mixed $value): int|float
    {
        if (! is_numeric($value)) {
            throw InvalidFieldException::mustBeNumeric($value);
        }

        if ($this->mustBeInteger && ! $this->isIntegerValue($value)) {
            throw InvalidFieldException::mustBeInteger($value);
        }

        $number = $this->mustBeInteger ? (int) $value : (float) $value;

        if ($this->mustBePositive === true && $number <= 0) {
            throw InvalidFieldException::mustBePositive($value);
        }

        if ($this->mustBePositive === false && $number >= 0) {
            throw InvalidFieldException::mustBeNegative($value);
        }

        return $number;
    }

    private function isIntegerValue(mixed $value): bool
    {
        return is_int($value) || (is_string($value) && ctype_digit(ltrim($value, '-')));
    }
}
