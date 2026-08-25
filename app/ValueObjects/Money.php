<?php

namespace App\ValueObjects;

use Brick\Math\RoundingMode;
use Brick\Money\AllocationMode;
use Brick\Money\Context\CustomContext;
use Brick\Money\Money as BrickMoney;

use function PHPUnit\Framework\isFloat;

final readonly class Money
{
    public function __construct(
        private BrickMoney $money
    ) {}

    public static function fromDecimal(string|int|float $amount, string $currency = 'BRL'): self
    {
        if (isFloat($amount)) {
            $amount = (strval($amount));
        }

        return new self(BrickMoney::of(
            amount: $amount,
            currency: $currency,
            context: new CustomContext(4),
            roundingMode: RoundingMode::HalfUp));
    }

    public static function fromStorage(?string $value, string $currency = 'BRL'): self
    {
        return self::fromDecimal($value ?? '0', currency: $currency);
    }

    public static function zero(string $currency = 'BRL'): self
    {
        return new self(BrickMoney::zero($currency, context: new CustomContext(4)));
    }

    public function add(Money $other): self
    {
        return new self($this->money->plus($other->money, RoundingMode::HalfUp));
    }

    public function subtract(Money $other): self
    {
        return new self($this->money->minus($other->money, RoundingMode::HalfUp));
    }

    public function multiply(string|int|float $factor): self
    {
        return new self($this->money->multipliedBy($factor, RoundingMode::HalfUp));
    }

    public function allocate(array $ratios): array
    {

        return array_map(
            fn (BrickMoney $m) => new self($m),
            $this->money->allocate($ratios, AllocationMode::FloorToLargestRemainder)
        );
    }

    public function isGreaterThan(Money $other): bool
    {
        return $this->money->isGreaterThan($other->money);
    }

    public function isZero(): bool
    {
        return $this->money->isZero();
    }

    public function toStorageString(): string
    {
        return (string) $this->money->getAmount();
    }

    public function formatted(): string
    {
        return $this->money->formatToLocale('pt_BR');
    }

    public function __toString(): string
    {
        return $this->toStorageString();
    }
}
