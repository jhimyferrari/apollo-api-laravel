<?php

use App\ValueObjects\Money;
use Brick\Money\Exception\MoneyMismatchException;

describe('Money', function () {

    describe('Money::fromDecimal', function () {
        it('should create an instance of a decimal string and convert to a 4 decimal places string', function () {
            $money = Money::fromDecimal('19.90');

            expect($money->toStorageString())->toBe('19.9000');
        });

        it('should create an instance of an int', function () {
            $money = Money::fromDecimal(100);

            expect($money->toStorageString())->toBe('100.0000');
        });

        it('should create an instance of an float', function () {
            $money = Money::fromDecimal(19.9);

            expect($money->toStorageString())->toBe('19.9000');
        });

        it('should preserve the 4 decimal places', function () {
            $money = Money::fromDecimal('19.9999');

            expect($money->toStorageString())->toBe('19.9999');
        });

        it('should round on half_up mode when has more than 4 decimal places', function () {
            $money = Money::fromDecimal('19.99995');

            expect($money->toStorageString())->toBe('20.0000');
        });

        it('should accept custom currency', function () {
            $money = Money::fromDecimal('10.00', 'USD');

            expect($money->formatted())->toContain('10');
        });
    });

    describe('Money::fromStorage', function () {
        it('should retrive a string from database preserving 4 decimals places', function () {
            $money = Money::fromStorage('1234.5678');

            expect($money->toStorageString())->toBe('1234.5678');
        });

        it('should return 0 when value it is null', function () {
            $money = Money::fromStorage(null);

            expect($money->isZero())->toBeTrue()
                ->and($money->toStorageString())->toBe('0.0000');
        });
    });

    describe('Money::zero', function () {
        it('should create a 0 instance with 4 decimal places ', function () {
            $money = Money::zero();

            expect($money->isZero())->toBeTrue()
                ->and($money->toStorageString())->toBe('0.0000');
        });
    });

    describe('Money::add', function () {
        it('should sum 2 values correctly', function () {
            $a = Money::fromDecimal('10.50');
            $b = Money::fromDecimal('5.25');

            expect($a->add($b)->toStorageString())->toBe('15.7500');
        });

        it('should sum 4 decimal places without loss precision', function () {
            $a = Money::fromDecimal('10.1234');
            $b = Money::fromDecimal('0.0001');

            expect($a->add($b)->toStorageString())->toBe('10.1235');
        });

        it('throws an excession when try to sum 2 differents currencies', function () {
            $brl = Money::fromDecimal('10.00', 'BRL');
            $usd = Money::fromDecimal('10.00', 'USD');

            expect(fn () => $brl->add($usd))->toThrow(MoneyMismatchException::class);
        });

        it('should not change original instance', function () {
            $a = Money::fromDecimal('10.00');
            $b = Money::fromDecimal('5.00');

            $a->add($b);

            expect($a->toStorageString())->toBe('10.0000');
        });
    });

    describe('Money::subtract', function () {
        it('should subtract 2 values correctly', function () {
            $a = Money::fromDecimal('10.50');
            $b = Money::fromDecimal('5.25');

            expect($a->subtract($b)->toStorageString())->toBe('5.2500');
        });

        it('should accept below 0 results', function () {
            $a = Money::fromDecimal('5.00');
            $b = Money::fromDecimal('10.00');

            expect($a->subtract($b)->toStorageString())->toBe('-5.0000');
        });

        it('throws an exception when try sub 2 differents currencies', function () {
            $brl = Money::fromDecimal('10.00', 'BRL');
            $usd = Money::fromDecimal('5.00', 'USD');

            expect(fn () => $brl->subtract($usd))->toThrow(MoneyMismatchException::class);
        });
    });

    describe('Money::multiply', function () {
        it('should multiply 2 values correctly', function () {
            $money = Money::fromDecimal('10.00');

            expect($money->multiply(3)->toStorageString())->toBe('30.0000');
        });

        it('should multiply by fractionated', function () {
            $money = Money::fromDecimal('10.00');

            expect($money->multiply('1.5')->toStorageString())->toBe('15.0000');
        });

        it('should preserve 4 decimal places', function () {
            $money = Money::fromDecimal('10.00');

            expect($money->multiply('0.12345')->toStorageString())->toBe('1.2345');
        });

        it('should round on half_up mode when has more than 4 decimal places', function () {
            $money = Money::fromDecimal('10.00');

            expect($money->multiply('0.123456')->toStorageString())->toBe('1.2346');
        });
    });

    describe('Money::allocate', function () {
        it('should allocate correctly', function () {
            $total = Money::fromDecimal('100.00');

            [$a, $b, $c] = $total->allocate([30, 30, 40]);

            expect($a->toStorageString())->toBe('30.0000')
                ->and($b->toStorageString())->toBe('30.0000')
                ->and($c->toStorageString())->toBe('40.0000');
        });

        it('ensure the sum of returned array is equal to initial value', function () {
            $total = Money::fromDecimal('100.00');

            $parts = $total->allocate([1, 1, 1]);

            $sum = array_reduce(
                $parts,
                fn (Money $carry, Money $part) => $carry->add($part),
                Money::zero()
            );

            expect($sum->toStorageString())->toBe($total->toStorageString());
        });

        it('allocate correctly 4 decimal places values', function () {
            $total = Money::fromDecimal('0.0003');

            $parts = $total->allocate([1, 1, 1]);

            $sum = array_reduce(
                $parts,
                fn (Money $carry, Money $part) => $carry->add($part),
                Money::zero()
            );

            expect($sum->toStorageString())->toBe('0.0003');
        });
    });

    describe('Money::isGreaterThan', function () {
        it('should return true when first value it is greater than second', function () {
            $a = Money::fromDecimal('10.00');
            $b = Money::fromDecimal('5.00');

            expect($a->isGreaterThan($b))->toBeTrue();
        });

        it('verify 4 decimal places', function () {
            $a = Money::fromDecimal('10.0002');
            $b = Money::fromDecimal('10.0001');

            expect($a->isGreaterThan($b))->toBeTrue();
        });

        it('should return false when it is lower or equal', function () {
            $a = Money::fromDecimal('5.00');
            $b = Money::fromDecimal('10.00');
            $c = Money::fromDecimal('10.00');

            expect($a->isGreaterThan($b))->toBeFalse()
                ->and($b->isGreaterThan($c))->toBeFalse();
        });
    });

    describe('Money::isZero', function () {
        it('should return true when is zero', function () {
            expect(Money::fromDecimal('0.00')->isZero())->toBeTrue();
        });

        it('should return false when have a value in 4 decimal place', function () {
            expect(Money::fromDecimal('0.0001')->isZero())->toBeFalse();
        });
    });

    describe('Money::formatted', function () {
        it('format on default value pt_BR', function () {
            $money = Money::fromDecimal('1234.56');

            expect($money->formatted())->toContain('1.234,56');
        });
    });

    describe('Money::__toString', function () {
        it('return the same string of StorageString', function () {
            $money = Money::fromDecimal('42.50');

            expect((string) $money)->toBe($money->toStorageString());
        });
    });

    describe('Money - it is compatible NUMERIC(15,4)', function () {
        it('keep the same scale (fromStorage -> operation -> toStorageString)', function () {
            $unitPrice = Money::fromStorage('19.9999');
            $total = $unitPrice->multiply(3);

            expect($total->toStorageString())->toBe('59.9997');
        });

        it('should not trunk on 2 decimal places', function () {
            $money = Money::fromDecimal('19.9999');

            expect($money->toStorageString())->not->toBe('20.00')
                ->and($money->toStorageString())->toBe('19.9999');
        });
    });

});
