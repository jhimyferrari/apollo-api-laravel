<?php

use App\Actions\Validation\ValidateProduct;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('ValidateProduct', function () {
    it('returns the product when a valid id is provided', function () {
        $product = Product::factory()->create();

        $result = app(ValidateProduct::class)->execute($product->id, 'product_id');

        expect($result->id)->toBe($product->id);
    });

    it('throws ValidationException when product is not found', function () {
        $nonExistentId = (string) Str::uuid();

        expect(fn () => app(ValidateProduct::class)->execute($nonExistentId, 'product_id'))
            ->toThrow(ValidationException::class, __('validation.product', ['attribute' => 'product_id']));
    });
});
