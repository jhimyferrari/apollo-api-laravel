<?php

use App\Enum\PermissionType;
use App\Enum\ProductUnit;
use App\Models\Brand;
use App\Models\Category;
use App\Models\NcmCode;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
describe('POST api/producst', function () {

    test('Logged user with valid data', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user, [PermissionType::PRODUCT_CREATE->value]);
        $brand = Brand::factory()->create(['organization_id' => $user->organization_id]);
        $categories = Category::factory()->count(5)->create(['organization_id' => $user->organization_id]);
        $data = [
            'name' => fake()->word(),
            'unit' => ProductUnit::random(),
            'ncm' => NcmCode::factory()->create()->code,
            'ean' => fake()->ean13(),
            'cost_price' => 5,
            'sale_price' => '10.0001',
            'brand_id' => $brand->id,
            'categories' => $categories->map(fn ($c) => ['id' => $c->id])->toArray(),
        ];

        $response = $this->postJson(route('v1.products.store'), $data);

        $response->assertCreated();
        // $this->assertDatabaseHas('products', $data);

    });
    test('Logged user with invalid data', function () {
        Sanctum::actingAs(User::factory()->create(), [PermissionType::PRODUCT_CREATE->value]);
        $response = $this->postJson(route('v1.products.store'), []);

        $response->assertUnprocessable();

    });
    test('Logged user without permission', function () {
        Sanctum::actingAs(User::factory()->create());
        $response = $this->postJson(route('v1.products.store'), []);

        $response->assertForbidden();
    });
    test('Non logged user', function () {
        $response = $this->postJson(route('v1.products.store'), []);
        $response->assertUnauthorized();
    });

    test('Logged user can create product with same ean as another organization, but fails within same organization', function () {
        $ean = '7891234567895';
        Product::factory()->create(['ean' => $ean]);

        $user = User::factory()->create();
        Sanctum::actingAs($user, [PermissionType::PRODUCT_CREATE->value]);

        $payload = [
            'name' => 'Produto Teste',
            'unit' => ProductUnit::Unit->value,
            'ean' => $ean,
            'cost_price' => 10,
            'sale_price' => 20,
        ];

        $this->postJson(route('v1.products.store'), $payload)->assertCreated();

        $this->postJson(route('v1.products.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ean', 'error.fields');
    });

    test('Logged user fails with 422 when categories have invalid or duplicate uuids or numeric overflow', function (array $override, string $errorField) {
        $user = User::factory()->create();
        Sanctum::actingAs($user, [PermissionType::PRODUCT_CREATE->value]);

        $payload = array_merge([
            'name' => 'Produto Teste',
            'unit' => ProductUnit::Unit->value,
            'cost_price' => 10,
            'sale_price' => 20,
        ], $override);

        $response = $this->postJson(route('v1.products.store'), $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors($errorField, 'error.fields');
    })->with([
        'invalid category uuid' => [['categories' => [['id' => 'invalid-uuid']]], 'categories.0.id'],
        'duplicate category uuid' => (function () {
            $uuid = '11111111-1111-4111-8111-111111111111';

            return [['categories' => [['id' => $uuid], ['id' => $uuid]]], 'categories.0.id'];
        })(),
        'stock_quantity overflow' => [['stock_quantity' => 99999999], 'stock_quantity'],
        'cost_price overflow' => [['cost_price' => 999999999999], 'cost_price'],
    ]);
});
