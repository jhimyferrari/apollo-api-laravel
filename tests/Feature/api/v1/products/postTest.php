<?php

use App\Enum\PermissionType;
use App\Enum\ProductUnit;
use App\Models\Brand;
use App\Models\Category;
use App\Models\NcmCode;
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
        $cost_price = fake()->randomNumber(7);
        $data = [
            'name' => fake()->word(),
            'unit' => ProductUnit::random(),
            'ncm' => NcmCode::factory()->create()->code,
            'ean' => fake()->ean13(),
            'cost_price' => $cost_price,
            'sale_price' => fake()->numberBetween($cost_price, 100000000),
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

        $response->assertNotFound();
    });
    test('Non logged user', function () {
        $response = $this->postJson(route('v1.products.store'), []);
        $response->assertUnauthorized();
    });
});
