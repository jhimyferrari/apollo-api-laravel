<?php

use App\Models\Client;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Supplier;
use App\Services\TreatmentService\Strategies\TreatClient;
use App\Services\TreatmentService\Strategies\TreatProduct;
use App\Services\TreatmentService\Strategies\TreatSeller;
use App\Services\TreatmentService\Strategies\TreatSupplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('TreatClient status validation', function () {
    it('allows active client by default', function () {
        $client = Client::factory()->create();

        $result = app(TreatClient::class)->handle($client->id, 'client_id');

        expect($result->id)->toBe($client->id);
    });

    it('blocks inactive client by default', function () {
        $client = Client::factory()->inactive()->create();

        expect(fn () => app(TreatClient::class)->handle($client->id, 'client_id'))
            ->toThrow(ValidationException::class, __('validation.resource_inactive', ['resource' => __('validation.resources.client')]));
    });

    it('allows inactive client when allowInactive is enabled', function () {
        $client = Client::factory()->inactive()->create();

        $result = app(TreatClient::class)->allowInactive()->handle($client->id, 'client_id');

        expect($result->id)->toBe($client->id);
    });
});

describe('TreatSeller status validation', function () {
    it('allows active seller by default', function () {
        $seller = Seller::factory()->create();

        $result = app(TreatSeller::class)->handle($seller->id, 'seller_id');

        expect($result->id)->toBe($seller->id);
    });

    it('blocks inactive seller by default', function () {
        $seller = Seller::factory()->inactive()->create();

        expect(fn () => app(TreatSeller::class)->handle($seller->id, 'seller_id'))
            ->toThrow(ValidationException::class, __('validation.resource_inactive', ['resource' => __('validation.resources.seller')]));
    });

    it('allows inactive seller when allowInactive is enabled', function () {
        $seller = Seller::factory()->inactive()->create();

        $result = app(TreatSeller::class)->allowInactive()->handle($seller->id, 'seller_id');

        expect($result->id)->toBe($seller->id);
    });
});

describe('TreatSupplier status validation', function () {
    it('allows active supplier by default', function () {
        $supplier = Supplier::factory()->create();

        $result = app(TreatSupplier::class)->handle($supplier->id, 'supplier_id');

        expect($result->id)->toBe($supplier->id);
    });

    it('blocks inactive supplier by default', function () {
        $supplier = Supplier::factory()->inactive()->create();

        expect(fn () => app(TreatSupplier::class)->handle($supplier->id, 'supplier_id'))
            ->toThrow(ValidationException::class, __('validation.resource_inactive', ['resource' => __('validation.resources.supplier')]));
    });

    it('allows inactive supplier when allowInactive is enabled', function () {
        $supplier = Supplier::factory()->inactive()->create();

        $result = app(TreatSupplier::class)->allowInactive()->handle($supplier->id, 'supplier_id');

        expect($result->id)->toBe($supplier->id);
    });
});

describe('TreatProduct status validation', function () {
    it('allows active product by default', function () {
        $product = Product::factory()->create();

        $result = app(TreatProduct::class)->handle($product->id, 'product_id');

        expect($result->id)->toBe($product->id);
    });

    it('blocks inactive product by default', function () {
        $product = Product::factory()->inactive()->create();

        expect(fn () => app(TreatProduct::class)->handle($product->id, 'product_id'))
            ->toThrow(ValidationException::class, __('validation.resource_inactive', ['resource' => __('validation.resources.product')]));
    });

    it('blocks discontinued product by default', function () {
        $product = Product::factory()->discontinued()->create();

        expect(fn () => app(TreatProduct::class)->handle($product->id, 'product_id'))
            ->toThrow(ValidationException::class, __('validation.resource_inactive', ['resource' => __('validation.resources.product')]));
    });

    it('allows inactive product when allowInactive is enabled', function () {
        $product = Product::factory()->inactive()->create();

        $result = app(TreatProduct::class)->allowInactive()->handle($product->id, 'product_id');

        expect($result->id)->toBe($product->id);
    });
});
