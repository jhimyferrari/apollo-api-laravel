<?php

use App\Actions\Validation\ValidateStatusEnum;
use App\Models\Client;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Supplier;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class);
describe('ValidateStatusEnum', function () {
    it('throw an error when pass an invalid status for Seller ', function () {
        expect(fn () => app(ValidateStatusEnum::class)->execute(new Seller, 'invalid status'))->toThrow(ValidationException::class, __('validation.status', ['attribute' => 'status', 'model' => 'Seller']));
    });

    it('throw an error when pass an invalid status for Client ', function () {
        expect(fn () => app(ValidateStatusEnum::class)->execute(new Client, 'invalid status'))->toThrow(ValidationException::class, __('validation.status', ['attribute' => 'status', 'model' => 'Client']));
    });

    it('throw an error when pass an invalid status for Supplier ', function () {
        expect(fn () => app(ValidateStatusEnum::class)->execute(new Supplier, 'invalid status'))->toThrow(ValidationException::class, __('validation.status', ['attribute' => 'status', 'model' => 'Supplier']));
    });

    it('throw an error when pass an invalid status for Product ', function () {
        expect(fn () => app(ValidateStatusEnum::class)->execute(new Product, 'invalid status'))->toThrow(ValidationException::class, __('validation.status', ['attribute' => 'status', 'model' => 'Product']));
    });
});
