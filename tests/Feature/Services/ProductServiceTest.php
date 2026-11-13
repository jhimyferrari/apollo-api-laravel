<?php

use App\Enum\Status\ProductStatus;
use App\Enum\StockMovementType;
use App\Exceptions\ResourceHasDependenciesException;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Client;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\Seller;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ProductService;
use App\ValueObjects\Money;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
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

        it('should always create the product with Active status regardless of the status passed', function () {
            $data = [
                'name' => 'productName',
                'cost_price' => '10.5000',
                'sale_price' => '19.9000',
                'status' => ProductStatus::Inactive->value,
            ];

            $product = $this->service->create($data, $this->user);

            expect($product->status)->toBe(ProductStatus::Active);
            $this->assertDatabaseHas('products', [
                'id' => $product->id,
                'status' => ProductStatus::Active->value,
            ]);
        });

        it('should create a Product with optional values successfully', function () {
            $brand = Brand::factory()->create(['organization_id' => $this->user->organization_id]);

            $data = [
                'name' => 'productName',
                'ean' => '7891234567895',
                'description' => 'Some description',
                'cost_price' => '10.5000',
                'sale_price' => '19.9000',
                'brand_id' => $brand->id,
            ];

            $product = $this->service->create($data, $this->user);

            expect($product)
                ->toBeInstanceOf(Product::class)
                ->description->toBe($data['description'])
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
            ], $this->user))->toThrow(ValidationException::class);
        });

        it('throws an error when not pass a name', function () {
            expect(fn () => $this->service->create([
                'name' => '',
                'cost_price' => '10.5000',
                'sale_price' => '19.9000',
            ], $this->user))->toThrow(ValidationException::class, __('validation.required', ['attribute' => __('validation.attributes.name')]));
        });

        it('throws an error when brand_id does not exist', function () {
            expect(fn () => $this->service->create([
                'name' => 'productName',
                'cost_price' => '10.5000',
                'sale_price' => '19.9000',
                'brand_id' => 999999,
            ], $this->user))->toThrow(ValidationException::class);
        });

        it('throws an error when pass an invalid unit', function () {
            expect(fn () => $this->service->create([
                'name' => 'productName',
                'cost_price' => '10.5000',
                'sale_price' => '19.9000',
                'unit' => 'INVALID_UNIT',
            ], $this->user))->toThrow(ValidationException::class);
        });

        it('not throws an error when pass a null initial stock', function () {
            expect(fn () => $this->service->create([
                'name' => 'productName',
                'cost_price' => '10.5000',
                'sale_price' => '19.9000',
                'stock_quantity' => null,
            ], $this->user))->not->toThrow(QueryException::class);
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

                ->toThrow(ValidationException::class);
        });

        it('should throw an error when pass an used ean', function () {
            $ean = Product::factory()->create([
                'organization_id' => $this->user->organization_id,
                'ean' => '7891234567895',
            ])->ean;
            $product = Product::factory()->create(['organization_id' => $this->user->organization_id]);

            expect(fn () => $this->service->update($product, ['ean' => $ean]))
                ->toThrow(ValidationException::class);
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
                ->toThrow(ValidationException::class, __('validation.required', ['attribute' => __('validation.attributes.name')]));
        });

        it('should update cost_price preserving 4 decimal places', function () {
            $product = Product::factory()->create(['organization_id' => $this->user->organization_id]);

            $updated = $this->service->update($product, ['cost_price' => '9.9999']);

            expect($updated->cost_price->toStorageString())->toBe('9.9999');
        });

        it('should update status successfully', function () {
            $product = Product::factory()->create(['organization_id' => $this->user->organization_id]);

            $updated = $this->service->update($product, ['status' => ProductStatus::Inactive->value]);

            expect($updated->status)->toBe(ProductStatus::Inactive);
            $this->assertDatabaseHas('products', [
                'id' => $product->id,
                'status' => ProductStatus::Inactive->value,
            ]);

            $updatedAgain = $this->service->update($product, ['status' => ProductStatus::Discontinued->value]);
            expect($updatedAgain->status)->toBe(ProductStatus::Discontinued);
        });

        it('should throw an error when pass an invalid status', function () {
            $product = Product::factory()->create(['organization_id' => $this->user->organization_id]);

            expect(fn () => $this->service->update($product, ['status' => 'invalid_status']))
                ->toThrow(ValidationException::class);
        });

        it('should update a description successfully', function () {
            $product = Product::factory()->create(['organization_id' => $this->user->organization_id]);

            $updated = $this->service->update($product, ['description' => 'other description']);

            expect($updated->description)->toBe('other description');

            $updatedAgain = $this->service->update($product, ['description' => null]);
            expect($updatedAgain->description)->toBeNull();
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

        it('throws an error when trying to delete a product with sales items', function () {
            $product = Product::factory()->create(['organization_id' => $this->user->organization_id]);
            $client = Client::factory()->create(['organization_id' => $this->user->organization_id]);
            $seller = Seller::factory()->create(['organization_id' => $this->user->organization_id]);
            $order = SalesOrder::factory()->create([
                'organization_id' => $this->user->organization_id,
                'client_id' => $client->id,
                'seller_id' => $seller->id,
            ]);

            $order->items()->create([
                'organization_id' => $this->user->organization_id,
                'product_id' => $product->id,
                'number' => 1,
                'quantity' => 2,
                'unit_price' => Money::fromDecimal('10.0000'),
                'subtotal' => Money::fromDecimal('20.0000'),
            ]);

            expect(fn () => $this->service->delete($product))
                ->toThrow(ResourceHasDependenciesException::class);
        });

        it('throws an error when trying to delete a product with purchase items', function () {
            $product = Product::factory()->create(['organization_id' => $this->user->organization_id]);
            $supplier = Supplier::factory()->create(['organization_id' => $this->user->organization_id]);
            $order = PurchaseOrder::factory()->create([
                'organization_id' => $this->user->organization_id,
                'supplier_id' => $supplier->id,
            ]);

            $order->items()->create([
                'organization_id' => $this->user->organization_id,
                'product_id' => $product->id,
                'number' => 1,
                'quantity' => 5,
                'unit_price' => Money::fromDecimal('5.0000'),
                'subtotal' => Money::fromDecimal('25.0000'),
            ]);

            expect(fn () => $this->service->delete($product))
                ->toThrow(ResourceHasDependenciesException::class);
        });

        it('throws an error when trying to delete a product with stock movements', function () {
            $product = Product::factory()->create(['organization_id' => $this->user->organization_id]);

            $movement = new StockMovement([
                'organization_id' => $this->user->organization_id,
                'product_id' => $product->id,
                'type' => StockMovementType::In,
                'quantity' => 10,
                'balance_after' => 10,
            ]);
            $movement->reference()->associate($product);
            $movement->save();

            expect(fn () => $this->service->delete($product))
                ->toThrow(ResourceHasDependenciesException::class);
        });
    });
});
