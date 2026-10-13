<?php

namespace App\Services\TreatmentService\Strategies;

use Illuminate\Validation\ValidationException;

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

    public function handle(mixed $value, string $field): int|float
    {
        if (! is_numeric($value)) {
            throw ValidationException::withMessages([$field => __('validation.numeric', ['attribute' => __("validation.attribute.$field")])]);
        }

        if ($this->mustBeInteger && ! $this->isIntegerValue($value)) {
            throw ValidationException::withMessages([$field => __('validation.integer', ['attribute' => __("validation.attribute.$field")])]);
        }

        $number = $this->mustBeInteger ? (int) $value : (float) $value;

        if ($this->mustBePositive === true && $number <= 0) {
            throw ValidationException::withMessages([$field => __('validation.numeric.positive', ['attribute' => __("validation.attribute.$field")])]);
        }

        if ($this->mustBePositive === false && $number >= 0) {

            throw ValidationException::withMessages([$field => __('validation.numeric.negative', ['attribute' => __("validation.attribute.$field")])]);
        }

        return $number;
    }

    private function isIntegerValue(mixed $value): bool
    {
        return is_int($value) || (is_string($value) && ctype_digit(ltrim($value, '-')));
    }
}
