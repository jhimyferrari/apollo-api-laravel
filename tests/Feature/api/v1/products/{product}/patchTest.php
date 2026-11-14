<?php

use App\Enum\PermissionType;
use App\Enum\ProductUnit;
use App\Enum\Status\ProductStatus;
use App\Models\NcmCode;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
describe('PATCH api/products/{product}', function () {

    test('Logged user with valid data', function () {
        $user = User::factory()->create();
        $product = Product::factory()->create(['organization_id' => $user->organization_id]);
        Sanctum::actingAs($user, [PermissionType::PRODUCT_UPDATE->value]);

        $cost_price = fake()->randomNumber(7);
        $ncm = NcmCode::factory()->create();
        $newProductRequest = [
            'name' => fake()->word,
            'unit' => fake()->randomElement(ProductUnit::allValues()),
            'ncm' => $ncm->code,
            'ean' => fake()->ean13(),
            'cost_price' => $cost_price,
            'sale_price' => fake()->numberBetween($cost_price, '100000000'),
        ];
        $response = $this->patchJson(route('v1.products.update', $product), $newProductRequest);

        $response->assertNoContent();

        unset($newProductRequest['ncm']);
        $newProductRequest['ncm_code_id'] = $ncm->id;

        $this->assertDatabaseHas('products', $newProductRequest);
    });

    test('Logged user can update product status', function () {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'organization_id' => $user->organization_id,
            'status' => ProductStatus::Active,
        ]);
        Sanctum::actingAs($user, [PermissionType::PRODUCT_UPDATE->value]);

        $cost_price = 1000;
        $payload = [
            'name' => 'Updated Product',
            'unit' => ProductUnit::Unit->value,
            'cost_price' => $cost_price,
            'sale_price' => 2000,
            'status' => ProductStatus::Inactive->value,
        ];
        $response = $this->patchJson(route('v1.products.update', $product), $payload);

        $response->assertNoContent();
        expect($product->fresh()->status)->toBe(ProductStatus::Inactive);

        $responseDiscontinued = $this->patchJson(route('v1.products.update', $product), [
            ...$payload,
            'status' => ProductStatus::Discontinued->value,
        ]);
        $responseDiscontinued->assertNoContent();
        expect($product->fresh()->status)->toBe(ProductStatus::Discontinued);
    });

    test('Logged user fails to update product with invalid status', function () {
        $user = User::factory()->create();
        $product = Product::factory()->create(['organization_id' => $user->organization_id]);
        Sanctum::actingAs($user, [PermissionType::PRODUCT_UPDATE->value]);

        $payload = [
            'name' => 'Updated Product',
            'unit' => ProductUnit::Unit->value,
            'cost_price' => 1000,
            'sale_price' => 2000,
            'status' => 'invalid_status',
        ];
        $response = $this->patchJson(route('v1.products.update', $product), $payload);

        $response->assertUnprocessable()
            ->assertJsonStructure(['error' => ['fields' => ['status']]]);
    });

    test('Other organization product', function () {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        Sanctum::actingAs($user, [PermissionType::PRODUCT_UPDATE->value]);

        $response = $this->patchJson(route('v1.products.update', $product), []);

        $response->assertNotFound();
    });
    test('Logged user with non valid data', function () {
        $user = User::factory()->create();
        $product = Product::factory()->create(['organization_id' => $user->organization_id]);
        Sanctum::actingAs($user, [PermissionType::PRODUCT_UPDATE->value]);
        $response = $this->patchJson(route('v1.products.update', $product), []);

        $response->assertUnprocessable();
    });
    test('Logged user without permission', function () {
        $user = User::factory()->create();
        $product = Product::factory()->create(['organization_id' => $user->organization_id]);
        Sanctum::actingAs($user);
        $response = $this->patchJson(route('v1.products.update', $product), []);

        $response->assertForbidden();

    });
    test('Non logged user', function () {
        $product = Product::factory()->create();
        $response = $this->patchJson(route('v1.products.update', $product), []);

        $response->assertUnauthorized();
    });
});
