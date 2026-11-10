<?php

use App\Actions\Validation\ValidateResourceStatus;
use App\Enum\Status\ClientStatus;
use App\Enum\Status\ProductStatus;
use App\Enum\Status\SellerStatus;
use App\Enum\Status\SupplierStatus;
use App\Models\Client;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Supplier;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class);

describe('ValidateResourceStatus', function () {
    it('does not throw an error when Client is active', function () {
        $client = new Client;
        $client->status = ClientStatus::Active;

        expect(fn () => app(ValidateResourceStatus::class)->execute($client, 'client_id'))->not->toThrow(ValidationException::class);
    });

    it('throws an error when Client is inactive', function () {
        $client = new Client;
        $client->status = ClientStatus::Inactive;

        expect(fn () => app(ValidateResourceStatus::class)->execute($client, 'client_id'))
            ->toThrow(ValidationException::class, __('validation.resource_inactive', ['resource' => __('validation.resources.client')]));
    });

    it('does not throw an error when Seller is active', function () {
        $seller = new Seller;
        $seller->status = SellerStatus::Active;

        expect(fn () => app(ValidateResourceStatus::class)->execute($seller, 'seller_id'))->not->toThrow(ValidationException::class);
    });

    it('throws an error when Seller is inactive', function () {
        $seller = new Seller;
        $seller->status = SellerStatus::Inactive;

        expect(fn () => app(ValidateResourceStatus::class)->execute($seller, 'seller_id'))
            ->toThrow(ValidationException::class, __('validation.resource_inactive', ['resource' => __('validation.resources.seller')]));
    });

    it('does not throw an error when Supplier is active', function () {
        $supplier = new Supplier;
        $supplier->status = SupplierStatus::Active;

        expect(fn () => app(ValidateResourceStatus::class)->execute($supplier, 'supplier_id'))->not->toThrow(ValidationException::class);
    });

    it('throws an error when Supplier is inactive', function () {
        $supplier = new Supplier;
        $supplier->status = SupplierStatus::Inactive;

        expect(fn () => app(ValidateResourceStatus::class)->execute($supplier, 'supplier_id'))
            ->toThrow(ValidationException::class, __('validation.resource_inactive', ['resource' => __('validation.resources.supplier')]));
    });

    it('does not throw an error when Product is active', function () {
        $product = new Product;
        $product->status = ProductStatus::Active;

        expect(fn () => app(ValidateResourceStatus::class)->execute($product, 'product_id'))->not->toThrow(ValidationException::class);
    });

    it('throws an error when Product is inactive', function () {
        $product = new Product;
        $product->status = ProductStatus::Inactive;

        expect(fn () => app(ValidateResourceStatus::class)->execute($product, 'product_id'))
            ->toThrow(ValidationException::class, __('validation.resource_inactive', ['resource' => __('validation.resources.product')]));
    });

    it('throws an error when Product is discontinued', function () {
        $product = new Product;
        $product->status = ProductStatus::Discontinued;

        expect(fn () => app(ValidateResourceStatus::class)->execute($product, 'product_id'))
            ->toThrow(ValidationException::class, __('validation.resource_inactive', ['resource' => __('validation.resources.product')]));
    });
});
