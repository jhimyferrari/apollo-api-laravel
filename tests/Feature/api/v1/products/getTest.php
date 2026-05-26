<?php

use App\Enum\PermissionType;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
describe('GET api/products', function () {

    test('Logged user with permission', function () {
        $user = User::factory()->create();

        Product::factory()->count(20)->create(['organization_id' => $user->organization_id]);
        Sanctum::actingAs($user, [PermissionType::PRODUCT_READ->value]);
        $response = $this->getJson(route('v1.products.index'));
        $response->assertOk();

        $response->assertJsonCount(15, 'data');

    });
    test('Logged user without permission', function () {
        Sanctum::actingAs(User::factory()->create());
        $response = $this->getJson(route('v1.products.index'));
        $response->assertNotFound();
    });
    test('Non logged user', function () {
        $response = $this->getJson(route('v1.products.index'));
        $response->assertUnauthorized();
    });
});
