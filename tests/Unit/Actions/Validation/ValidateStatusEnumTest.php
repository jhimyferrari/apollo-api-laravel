<?php

use App\Actions\Validation\ValidateStatusEnum;
use App\Exceptions\InvalidStatusException;
use App\Models\Client;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Supplier;

describe('ValidateStatusEnum', function () {
    it('throw an error when pass an invalid status for Seller ', function () {
        expect(fn () => app(ValidateStatusEnum::class)->execute(new Seller, 'invalid status'))->toThrow(InvalidStatusException::class, '`invalid status` status doesn`t exist for Seller');
    });

    it('throw an error when pass an invalid status for Client ', function () {
        expect(fn () => app(ValidateStatusEnum::class)->execute(new Client, 'invalid status'))->toThrow(InvalidStatusException::class, '`invalid status` status doesn`t exist for Client');
    });

    it('throw an error when pass an invalid status for Supplier ', function () {
        expect(fn () => app(ValidateStatusEnum::class)->execute(new Supplier, 'invalid status'))->toThrow(InvalidStatusException::class, '`invalid status` status doesn`t exist for Supplier');
    });

    it('throw an error when pass an invalid status for Product ', function () {
        expect(fn () => app(ValidateStatusEnum::class)->execute(new Product, 'invalid status'))->toThrow(InvalidStatusException::class, '`invalid status` status doesn`t exist for Product');
    });
});
