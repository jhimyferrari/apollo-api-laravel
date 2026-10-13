<?php

namespace App\Services\TreatmentService\Strategies;

use App\Exceptions\InvalidFieldException;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class TreatArray implements TreatmentStrategy
{
    public function __construct(
        private readonly bool $mustNotBeEmpty = false,
        private readonly ?int $minItems = null,
        private readonly ?int $maxItems = null,
        private readonly bool $mustBeUnique = false,
        private readonly ?string $elementType = null,
    ) {}

    public function notEmpty(): self
    {
        return new self(
            mustNotBeEmpty: true,
            minItems: $this->minItems,
            maxItems: $this->maxItems,
            mustBeUnique: $this->mustBeUnique,
            elementType: $this->elementType,
        );
    }

    public function minItems(int $min): self
    {
        return new self(
            mustNotBeEmpty: $this->mustNotBeEmpty,
            minItems: $min,
            maxItems: $this->maxItems,
            mustBeUnique: $this->mustBeUnique,
            elementType: $this->elementType,
        );
    }

    public function maxItems(int $max): self
    {
        return new self(
            mustNotBeEmpty: $this->mustNotBeEmpty,
            minItems: $this->minItems,
            maxItems: $max,
            mustBeUnique: $this->mustBeUnique,
            elementType: $this->elementType,
        );
    }

    public function unique(): self
    {
        return new self(
            mustNotBeEmpty: $this->mustNotBeEmpty,
            minItems: $this->minItems,
            maxItems: $this->maxItems,
            mustBeUnique: true,
            elementType: $this->elementType,
        );
    }

    public function ofType(string $type): self
    {
        return new self(
            mustNotBeEmpty: $this->mustNotBeEmpty,
            minItems: $this->minItems,
            maxItems: $this->maxItems,
            mustBeUnique: $this->mustBeUnique,
            elementType: $type,
        );
    }

    public function handle(mixed $value, string $field): array
    {
        if (! is_array($value)) {
            report(InvalidFieldException::mustBeArray($value));
            throw new RuntimeException(__('errors.internal_error'));
        }

        if ($this->mustNotBeEmpty && count($value) === 0) {
            throw ValidationException::withMessages([$field => __('validation.required', ['attribute' => __("validation.attributes.$field")])]);
        }

        if ($this->minItems !== null && count($value) < $this->minItems) {
            throw ValidationException::withMessages([$field => __('validation.min.array',
                ['attribute' => __("validation.attributes.$field"),
                    'min' => $this->minItems])]);
        }

        if ($this->maxItems !== null && count($value) > $this->maxItems) {
            throw ValidationException::withMessages([$field => __('validation.max.array',
                ['attribute' => __("validation.attributes.$field"),
                    'max' => $this->maxItems])]);
        }

        if ($this->mustBeUnique && count($value) !== count(array_unique($value, SORT_REGULAR))) {
            throw ValidationException::withMessages([$field => __('validation.distinct', ['attribute' => __("validation.attributes.$field")])]);
        }

        if ($this->elementType !== null) {
            foreach ($value as $index => $item) {
                if (! $this->matchesType($item, $this->elementType)) {
                    report(new InvalidFieldException("Array should has $this->elementType but has".\class_basename($item).' type'));
                    throw new RuntimeException(__('errors.internal_error'));
                }
            }
        }

        return $value;
    }

    private function matchesType(mixed $item, string $type): bool
    {
        return match ($type) {
            'int' => is_int($item),
            'float' => is_float($item) || is_int($item),
            'string' => is_string($item),
            'array' => is_array($item),
            'bool' => is_bool($item),
            default => throw new \InvalidArgumentException("Unsupported element type: {$type}"),
        };
    }
}
