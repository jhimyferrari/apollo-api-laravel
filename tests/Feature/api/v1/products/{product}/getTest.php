<?php

use App\Enum\PermissionType;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
describe('GET api/products/{product}', function () {
    test('Logged user with permission', function () {
        $user = User::factory()->create();
        $product = Product::factory()->create(['organization_id' => $user->organization_id]);
        Sanctum::actingAs($user, [PermissionType::PRODUCT_READ->value]);

        $product->refresh();

        $response = $this->getJson(route('v1.products.show', $product));
        $response->assertOk()
            ->assertJson(ProductResource::make($product)->response()->getData(true));
    });
    test('Other Organization product', function () {
        $product = Product::factory()->create();
        Sanctum::actingAs(User::factory()->create(), [PermissionType::PRODUCT_READ]);
        $response = $this->getJson(route('v1.products.show', $product));
        $response->assertNotFound();
    });
    test('Logged user without permission', function () {
        $user = User::factory()->create();
        $product = Product::factory()->create(['organization_id' => $user->organization_id]);
        Sanctum::actingAs($user);
        $response = $this->getJson(route('v1.products.show', $product));
        $response->assertNotFound();
    });
    test('Non logged user', function () {
        $product = Product::factory()->create();
        $response = $this->getJson(route('v1.products.show', $product));
        $response->assertUnauthorized();
    });
});
