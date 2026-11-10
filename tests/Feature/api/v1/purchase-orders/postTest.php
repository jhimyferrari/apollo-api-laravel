<?php

use App\Enum\PermissionType;
use App\Enum\Status\OrderStatus;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
describe('POST api/purchase-orders', function () {
    test('Logged user with valid data', function () {
        $user = User::factory()->create();
        $product = Product::factory()->for($user->organization)->withStock(10)->create();
        $supplier = Supplier::factory()->for($user->organization)->create();

        Sanctum::actingAs($user, [PermissionType::PURCHASE_ORDER_CREATE->value]);

        $response = $this->postJson(route('v1.purchase-orders.store'), [
            'supplier_id' => $supplier->id,
            'items' => [
                ['product_id' => $product->id, 'number' => 1, 'quantity' => 5],
            ],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.supplier_id', $supplier->id);
        $response->assertJsonPath('data.status', OrderStatus::Draft->value);
        $response->assertJsonCount(1, 'data.items');

        $this->assertDatabaseHas('purchase_orders', [
            'supplier_id' => $supplier->id,
            'organization_id' => $user->organization_id,
        ]);
    });

    test('Non logged user', function () {
        $response = $this->postJson(route('v1.purchase-orders.store'), []);
        $response->assertUnauthorized();
    });

    test('Logged user without permission', function () {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson(route('v1.purchase-orders.store'), []);
        $response->assertForbidden();
    });

    test('Logged user with missing supplier_id', function () {
        $user = User::factory()->create();
        $product = Product::factory()->for($user->organization)->withStock(10)->create();

        Sanctum::actingAs($user, [PermissionType::PURCHASE_ORDER_CREATE->value]);

        $response = $this->postJson(route('v1.purchase-orders.store'), [
            'items' => [
                ['id' => $product->id, 'number' => 1, 'quantity' => 5],
            ],
        ]);

        $response->assertUnprocessable();
    });

    test('Logged user with supplier_id from a different organization', function () {
        $user = User::factory()->create();
        $product = Product::factory()->for($user->organization)->withStock(10)->create();
        $otherOrganizationSupplier = Supplier::factory()->create();

        Sanctum::actingAs($user, [PermissionType::PURCHASE_ORDER_CREATE->value]);

        $response = $this->postJson(route('v1.purchase-orders.store'), [
            'supplier_id' => $otherOrganizationSupplier->id,
            'items' => [
                ['id' => $product->id, 'number' => 1, 'quantity' => 5],
            ],
        ]);

        $response->assertUnprocessable();
    });

    test('Logged user with empty items', function () {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->for($user->organization)->create();

        Sanctum::actingAs($user, [PermissionType::PURCHASE_ORDER_CREATE->value]);

        $response = $this->postJson(route('v1.purchase-orders.store'), [
            'supplier_id' => $supplier->id,
            'items' => [],
        ]);

        $response->assertUnprocessable();

    });

    test('Logged user with negative item quantity', function () {
        $user = User::factory()->create();
        $product = Product::factory()->for($user->organization)->withStock(10)->create();
        $supplier = Supplier::factory()->for($user->organization)->create();

        Sanctum::actingAs($user, [PermissionType::PURCHASE_ORDER_CREATE->value]);

        $response = $this->postJson(route('v1.purchase-orders.store'), [
            'supplier_id' => $supplier->id,
            'items' => [
                ['id' => $product->id, 'number' => 1, 'quantity' => -5],
            ],
        ]);

        $response->assertUnprocessable();
    });

    test('Logged user with inactive supplier', function () {
        $user = User::factory()->create();
        $product = Product::factory()->for($user->organization)->withStock(10)->create();
        $supplier = Supplier::factory()->for($user->organization)->inactive()->create();

        Sanctum::actingAs($user, [PermissionType::PURCHASE_ORDER_CREATE->value]);

        $response = $this->postJson(route('v1.purchase-orders.store'), [
            'supplier_id' => $supplier->id,
            'items' => [
                ['product_id' => $product->id, 'number' => 1, 'quantity' => 5],
            ],
        ]);

        $response->assertUnprocessable();
        expect($response->json('error.fields.supplier_id'))->not->toBeNull();
    });

    test('Logged user with inactive product', function () {
        $user = User::factory()->create();
        $product = Product::factory()->for($user->organization)->withStock(10)->inactive()->create();
        $supplier = Supplier::factory()->for($user->organization)->create();

        Sanctum::actingAs($user, [PermissionType::PURCHASE_ORDER_CREATE->value]);

        $response = $this->postJson(route('v1.purchase-orders.store'), [
            'supplier_id' => $supplier->id,
            'items' => [
                ['product_id' => $product->id, 'number' => 1, 'quantity' => 5],
            ],
        ]);

        $response->assertUnprocessable();
        expect($response->json('error.fields.product_id'))->not->toBeNull();
    });
});
