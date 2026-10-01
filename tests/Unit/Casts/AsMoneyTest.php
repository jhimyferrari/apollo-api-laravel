<?php

use App\Casts\AsMoney;
use App\ValueObjects\Money;
use Illuminate\Database\Eloquent\Model;

describe('AsMoney', function () {

    beforeEach(function () {
        $this->cast = new AsMoney;

        // Model concreto e "vazio" — o parâmetro $model não é usado pela lógica do cast,
        // então uma instância anônima serve só para satisfazer a assinatura.
        $this->model = new class extends Model {};
    });

    describe('get()', function () {
        it('returns null when the stored value is null', function () {
            $result = $this->cast->get($this->model, 'unit_price', null, []);

            expect($result)->toBeNull();
        });

        it('returns a Money instance built from the stored value', function () {
            $result = $this->cast->get($this->model, 'unit_price', '10.5000', []);

            expect($result)->toBeInstanceOf(Money::class);
        });

        it('round-trips correctly through fromStorage/toStorageString', function () {
            $stored = '1234.5678';

            $result = $this->cast->get($this->model, 'unit_price', $stored, []);

            expect($result->toStorageString())->toBe($stored);
        });
    });

    describe('set()', function () {
        it('returns null when the given value is null', function () {
            $result = $this->cast->set($this->model, 'unit_price', null, []);

            expect($result)->toBeNull();
        });

        it('returns the storage string for a valid Money instance', function () {
            $money = Money::fromStorage('99.9900');

            $result = $this->cast->set($this->model, 'unit_price', $money, []);

            expect($result)->toBe('99.9900');
        });

        it('throws when the value is not a Money instance', function () {
            expect(fn () => $this->cast->set($this->model, 'unit_price', 10.5, []))
                ->toThrow(RuntimeException::class, 'The field `unit_price` must be of type Money, double received');
        });

        it('throws when a raw string is given instead of a Money instance', function () {
            expect(fn () => $this->cast->set($this->model, 'unit_price', '10.50', []))
                ->toThrow(RuntimeException::class, 'The field `unit_price` must be of type Money, string received');
        });

        it('throws when an array is given instead of a Money instance', function () {
            expect(fn () => $this->cast->set($this->model, 'unit_price', ['amount' => 10.5], []))
                ->toThrow(RuntimeException::class, 'The field `unit_price` must be of type Money, array received');
        });
    });

    describe('get() and set() together', function () {
        it('produces a value that can be written and read back identically', function () {
            $original = Money::fromStorage('555.5500');

            $storageValue = $this->cast->set($this->model, 'unit_price', $original, []);
            $rehydrated = $this->cast->get($this->model, 'unit_price', $storageValue, []);

            expect($rehydrated->toStorageString())->toBe($original->toStorageString());
        });
    });
});
