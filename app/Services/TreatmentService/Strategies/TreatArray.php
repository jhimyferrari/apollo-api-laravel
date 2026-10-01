<?php

namespace App\Services\TreatmentService\Strategies;

use App\Exceptions\InvalidFieldException;

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

    public function handle(mixed $value): array
    {
        if (! is_array($value)) {
            throw InvalidFieldException::mustBeArray();
        }

        if ($this->mustNotBeEmpty && count($value) === 0) {
            throw InvalidFieldException::mustNotBeEmptyArray();
        }

        if ($this->minItems !== null && count($value) < $this->minItems) {
            throw InvalidFieldException::minItems($this->minItems);
        }

        if ($this->maxItems !== null && count($value) > $this->maxItems) {
            throw InvalidFieldException::maxItems($this->maxItems);
        }

        if ($this->mustBeUnique && count($value) !== count(array_unique($value, SORT_REGULAR))) {
            throw InvalidFieldException::mustHaveUniqueItems();
        }

        if ($this->elementType !== null) {
            foreach ($value as $index => $item) {
                if (! $this->matchesType($item, $this->elementType)) {
                    throw InvalidFieldException::invalidArrayElementType($index, $this->elementType);
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
