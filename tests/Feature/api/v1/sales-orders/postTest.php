<?php

use App\Enum\PermissionType;
use App\Enum\Status\OrderStatus;
use App\Models\Client;
use App\Models\Product;
use App\Models\Seller;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

describe('POST api/sales-orders', function () {
    test('Logged user with valid data', function () {
        $user = User::factory()->create();
        $product = Product::factory()->for($user->organization)->withStock(10)->create();
        $client = Client::factory()->for($user->organization)->create();
        $seller = Seller::factory()->for($user->organization)->create();

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_CREATE->value]);

        $response = $this->postJson(route('v1.sales-orders.store'), [
            'client_id' => $client->id,
            'seller_id' => $seller->id,
            'items' => [
                ['product_id' => $product->id, 'number' => 1, 'quantity' => 5],
            ],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.client_id', $client->id);
        $response->assertJsonPath('data.seller_id', $seller->id);
        $response->assertJsonPath('data.status', OrderStatus::Draft->value);
        $response->assertJsonCount(1, 'data.items');

        $this->assertDatabaseHas('sales_orders', [
            'client_id' => $client->id,
            'organization_id' => $user->organization_id,
        ]);
    });

    test('Non logged user', function () {
        $response = $this->postJson(route('v1.sales-orders.store'), []);
        $response->assertUnauthorized();
    });

    test('Logged user without permission', function () {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson(route('v1.sales-orders.store'), []);
        $response->assertNotFound();
    });

    test('Logged user with missing client_id', function () {
        $user = User::factory()->create();
        $product = Product::factory()->for($user->organization)->withStock(10)->create();
        $seller = Seller::factory()->for($user->organization)->create();

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_CREATE->value]);

        $response = $this->postJson(route('v1.sales-orders.store'), [
            'seller_id' => $seller->id,
            'items' => [
                ['id' => $product->id, 'number' => 1, 'quantity' => 5],
            ],
        ]);

        $response->assertUnprocessable();
    });

    test('Logged user with client_id from a different organization', function () {
        $user = User::factory()->create();
        $product = Product::factory()->for($user->organization)->withStock(10)->create();
        $seller = Seller::factory()->for($user->organization)->create();
        $otherOrganizationClient = Client::factory()->create();

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_CREATE->value]);

        $response = $this->postJson(route('v1.sales-orders.store'), [
            'client_id' => $otherOrganizationClient->id,
            'seller_id' => $seller->id,
            'items' => [
                ['id' => $product->id, 'number' => 1, 'quantity' => 5],
            ],
        ]);

        $response->assertUnprocessable();
    });

    test('Logged user with empty items', function () {
        $user = User::factory()->create();
        $client = Client::factory()->for($user->organization)->create();
        $seller = Seller::factory()->for($user->organization)->create();

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_CREATE->value]);

        $response = $this->postJson(route('v1.sales-orders.store'), [
            'client_id' => $client->id,
            'seller_id' => $seller->id,
            'items' => [],
        ]);

        $response->assertUnprocessable();

    });

    test('Logged user with negative item quantity', function () {
        $user = User::factory()->create();
        $product = Product::factory()->for($user->organization)->withStock(10)->create();
        $client = Client::factory()->for($user->organization)->create();
        $seller = Seller::factory()->for($user->organization)->create();

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_CREATE->value]);

        $response = $this->postJson(route('v1.sales-orders.store'), [
            'client_id' => $client->id,
            'seller_id' => $seller->id,
            'items' => [
                ['id' => $product->id, 'number' => 1, 'quantity' => -5],
            ],
        ]);

        $response->assertUnprocessable();
    });
});
