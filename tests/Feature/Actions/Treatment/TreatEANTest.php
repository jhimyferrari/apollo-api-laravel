<?php

use App\Actions\Treatment\TreatEAN;
use App\Exceptions\DuplicateFieldException;
use App\Exceptions\InvalidFieldException;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
describe('TreatEAN', function () {
    it('should return null when passing an empty value and it is not required', function () {
        $result = app(TreatEAN::class)->execute(
            model: new Product,
            field: 'ean',
            value: '',
            mustBeNotNull: false,
            mustBeUnique: false
        );

        expect($result)->toBeNull();
    });

    it('should return null when passing a value with only spaces and it is not required', function () {
        $result = app(TreatEAN::class)->execute(
            model: new Product,
            field: 'ean',
            value: '             ', // 13 espaços, mesmo tamanho de um EAN-13
            mustBeNotNull: false,
            mustBeUnique: false
        );

        expect($result)->toBeNull();
    });

    it('should return the clean value when passing a valid EAN-13', function () {
        $ean = fake()->ean13();

        $result = app(TreatEAN::class)->execute(
            model: new Product,
            field: 'ean',
            value: $ean,
            mustBeNotNull: false,
            mustBeUnique: false
        );

        expect($result)->toBe($ean);
    });

    it('should trim surrounding spaces before validating', function () {
        $ean = fake()->ean13();

        $result = app(TreatEAN::class)->execute(
            model: new Product,
            field: 'ean',
            value: "  {$ean}  ",
            mustBeNotNull: false,
            mustBeUnique: false
        );
        expect($result)->toBe($ean);
    });

    it('throw an error when the EAN checksum is invalid', function () {
        expect(fn () => app(TreatEAN::class)->execute(
            model: new Product,
            field: 'ean',
            value: '1234567890123',
            mustBeNotNull: false,
            mustBeUnique: false
        ))->toThrow(InvalidFieldException::class);
    });

    it('throw an error when the EAN has an invalid length', function () {
        expect(fn () => app(TreatEAN::class)->execute(
            model: new Product,
            field: 'ean',
            value: '123456789',
            mustBeNotNull: false,
            mustBeUnique: false
        ))->toThrow(InvalidFieldException::class);
    });

    it('throw an error when the value is empty and it is required', function () {
        expect(fn () => app(TreatEAN::class)->execute(
            model: new Product,
            field: 'ean',
            value: '',
            mustBeNotNull: true,
            mustBeUnique: false
        ))->toThrow(InvalidFieldException::class);
    });

    it('throw an error when the value is only spaces and it is required', function () {
        expect(fn () => app(TreatEAN::class)->execute(
            model: new Product,
            field: 'ean',
            value: '   ',
            mustBeNotNull: true,
            mustBeUnique: false
        ))->toThrow(InvalidFieldException::class);
    });

    it('throw an error when the EAN already exists', function () {
        $ean = fake()->ean13();
        Product::factory()->create(['ean' => $ean]);

        expect(fn () => app(TreatEAN::class)->execute(
            model: new Product,
            field: 'ean',
            value: $ean,
            mustBeNotNull: false,
            mustBeUnique: true
        ))->toThrow(DuplicateFieldException::class);
    });

    it('should not throw when passing the same EAN as before, ignoring its own id', function () {
        $ean = fake()->ean13();
        $product = Product::factory()->create(['ean' => $ean]);

        $result = app(TreatEAN::class)->execute(
            model: new Product,
            field: 'ean',
            value: $product->ean,
            mustBeNotNull: false,
            mustBeUnique: true,
            ignoredId: $product->id
        );

        expect($result)->toBe($ean);
    });

    it('throw an error when the EAN already exists within the same organization', function () {
        $ean = fake()->ean13();
        $product = Product::factory()->create(['ean' => $ean]);

        expect(fn () => app(TreatEAN::class)->execute(
            model: new Product,
            field: 'ean',
            value: $ean,
            mustBeNotNull: false,
            mustBeUnique: true,
            organizationId: $product->organization_id
        ))->toThrow(DuplicateFieldException::class);
    });

    it('should not throw when EAN does not exist and mustBeUnique is true', function () {
        $ean = fake()->ean13();

        $result = app(TreatEAN::class)->execute(
            model: new Product,
            field: 'ean',
            value: $ean,
            mustBeNotNull: false,
            mustBeUnique: true
        );

        expect($result)->toBe($ean);
    });
});
