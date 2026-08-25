<?php

use App\Exceptions\DuplicateFieldException;
use App\Exceptions\InvalidFieldException;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductService;
use App\ValueObjects\Money;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
    $this->service = app(ProductService::class);
});

describe('ProductService', function () {
    describe('create', function () {
        it('should create a Product successfully', function () {
            $data = [
                'name' => 'productName',
                'cost_price' => '10.5000',
                'sale_price' => '19.9000',
            ];

            $product = $this->service->create($data, $this->user);

            expect($product)
                ->toBeInstanceOf(Product::class)
                ->name->toBe($data['name'])
                ->ean->toBeNull()
                ->brand_id->toBeNull();

            expect($product->cost_price)->toBeInstanceOf(Money::class);
            expect($product->cost_price->toStorageString())->toBe('10.5000');
            expect($product->sale_price->toStorageString())->toBe('19.9000');
        });

        it('should create a Product with optional values successfully', function () {
            $brand = Brand::factory()->create(['organization_id' => $this->user->organization_id]);

            $data = [
                'name' => 'productName',
                'ean' => '7891234567895',
                'cost_price' => '10.5000',
                'sale_price' => '19.9000',
                'brand_id' => $brand->id,
            ];

            $product = $this->service->create($data, $this->user);

            expect($product)
                ->toBeInstanceOf(Product::class)
                ->name->toBe($data['name'])
                ->ean->toBe($data['ean'])
                ->brand_id->toBe($brand->id);
        });

        it('should remove spaces from name', function () {
            $data = [
                'name' => ' productName ',
                'cost_price' => '10.5000',
                'sale_price' => '19.9000',
            ];

            $product = $this->service->create($data, $this->user);

            expect($product)
                ->toBeInstanceOf(Product::class)
                ->name->toBe('productName');
        });

        it('should sync categories on create', function () {
            $categories = Category::factory()->count(7)->create(['organization_id' => $this->user->organization_id]);

            $data = [
                'name' => 'productName',
                'cost_price' => '10.5000',
                'sale_price' => '19.9000',
                'categories' => $categories->map(fn ($categories) => ['id' => $categories->id])->toArray(),
            ];

            $product = $this->service->create($data, $this->user);

            expect($product->categories()->pluck('id')->sort()->values()->toArray())
                ->toBe($categories->pluck('id')->sort()->values()->toArray());
        });

        it('throws an error when pass an used ean', function () {
            $ean = Product::factory()->create([
                'organization_id' => $this->user->organization_id,
                'ean' => '7891234567895',
            ])->ean;

            expect(fn () => $this->service->create([
                'name' => 'productName',
                'cost_price' => '10.5000',
                'sale_price' => '19.9000',
                'ean' => $ean,
            ], $this->user))->toThrow(DuplicateFieldException::class);
        });

        it('throws an error when not pass a name', function () {
            expect(fn () => $this->service->create([
                'name' => '',
                'cost_price' => '10.5000',
                'sale_price' => '19.9000',
            ], $this->user))->toThrow(InvalidFieldException::class, 'The field `name` must have a value');
        });

        it('throws an error when brand_id does not exist', function () {
            expect(fn () => $this->service->create([
                'name' => 'productName',
                'cost_price' => '10.5000',
                'sale_price' => '19.9000',
                'brand_id' => 999999,
            ], $this->user))->toThrow(ModelNotFoundException::class);
        });

        // AJUSTAR: troque InvalidFieldException pela exception real lançada por ValidateUnitProduct
        it('throws an error when pass an invalid unit', function () {
            expect(fn () => $this->service->create([
                'name' => 'productName',
                'cost_price' => '10.5000',
                'sale_price' => '19.9000',
                'unit' => 'INVALID_UNIT',
            ], $this->user))->toThrow(InvalidFieldException::class);
        });
    });

    describe('update', function () {
        it('should update a Product successfully', function () {
            $product = Product::factory()->create(['organization_id' => $this->user->organization_id]);

            $data = [
                'name' => 'updatedName',
                'cost_price' => '15.0000',
                'sale_price' => '25.0000',
            ];

            $updated = $this->service->update($product, $data);

            expect($updated)
                ->toBeInstanceOf(Product::class)
                ->name->toBe($data['name']);

            expect($updated->cost_price->toStorageString())->toBe('15.0000');
            expect($updated->sale_price->toStorageString())->toBe('25.0000');
        });

        it('should not change fields absent from the payload (partial update)', function () {
            $product = Product::factory()->create([
                'organization_id' => $this->user->organization_id,
                'name' => 'originalName',
            ]);

            $this->service->update($product, ['cost_price' => '15.0000']);

            expect($product->fresh())
                ->name->toBe('originalName');
        });

        it('should link a brand when a valid brand_id is provided', function () {
            $product = Product::factory()->create([
                'organization_id' => $this->user->organization_id,
                'brand_id' => null,
            ]);
            $brand = Brand::factory()->create(['organization_id' => $this->user->organization_id]);

            $updated = $this->service->update($product, ['brand_id' => $brand->id]);

            expect($updated->brand_id)->toBe($brand->id);
        });

        it('should unlink the brand when brand_id is explicitly null', function () {
            $brand = Brand::factory()->create(['organization_id' => $this->user->organization_id]);
            $product = Product::factory()->create([
                'organization_id' => $this->user->organization_id,
                'brand_id' => $brand->id,
            ]);

            $updated = $this->service->update($product, ['brand_id' => null]);

            expect($updated->brand_id)->toBeNull();
        });

        it('should throw an error when brand_id does not exist', function () {
            $product = Product::factory()->create(['organization_id' => $this->user->organization_id]);

            expect(fn () => $this->service->update($product, ['brand_id' => 999999]))

                ->toThrow(ModelNotFoundException::class);
        });

        it('should throw an error when pass an used ean', function () {
            $ean = Product::factory()->create([
                'organization_id' => $this->user->organization_id,
                'ean' => '7891234567895',
            ])->ean;
            $product = Product::factory()->create(['organization_id' => $this->user->organization_id]);

            expect(fn () => $this->service->update($product, ['ean' => $ean]))
                ->toThrow(DuplicateFieldException::class);
        });

        it('should update successfully when passing the same ean as before', function () {
            $product = Product::factory()->create([
                'organization_id' => $this->user->organization_id,
                'ean' => '7891234567895',
            ]);

            $updated = $this->service->update($product, ['ean' => $product->ean]);

            expect($updated)
                ->toBeInstanceOf(Product::class)
                ->ean->toBe('7891234567895');
        });

        it('should update categories via sync', function () {
            $categoryA = Category::factory()->create(['organization_id' => $this->user->organization_id]);
            $categoryB = Category::factory()->create(['organization_id' => $this->user->organization_id]);
            $product = Product::factory()->create(['organization_id' => $this->user->organization_id]);
            $product->categories()->sync([$categoryA->id]);

            $this->service->update($product, ['categories' => [['id' => $categoryB->id]]]);

            expect($product->categories()->pluck('categories.id')->all())->toBe([$categoryB->id]);
        });

        it('throws an error when pass an empty name', function () {
            $product = Product::factory()->create(['organization_id' => $this->user->organization_id]);

            expect(fn () => $this->service->update($product, ['name' => '']))
                ->toThrow(InvalidFieldException::class, 'The field `name` must have a value');
        });

        it('should update cost_price preserving 4 decimal places', function () {
            $product = Product::factory()->create(['organization_id' => $this->user->organization_id]);

            $updated = $this->service->update($product, ['cost_price' => '9.9999']);

            expect($updated->cost_price->toStorageString())->toBe('9.9999');
        });
    });

    describe('delete', function () {
        it('should delete a product', function () {
            $product = Product::factory()->create(['organization_id' => $this->user->organization_id]);

            $this->service->delete($product);

            $this->assertSoftDeleted($product);
        });
        it('should softDelete some product and detach it from categories tables', function () {
            $categories = Category::factory()->count(3)->create(['organization_id' => $this->user->organization_id]);
            $products = Product::factory()->count(10)->withCategories($categories)->create();
            $product = $products->pop();

            $this->service->delete($product);

            $this->assertSoftDeleted($product)
                ->assertFalse($categories->first()->products->contains($product));

            $this->assertEmpty($categories->first()->products->diff($products));
        });
    });
});
