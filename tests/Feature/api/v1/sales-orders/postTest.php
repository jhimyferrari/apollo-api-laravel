<?php

use App\Enum\PermissionType;
use App\Enum\Status\OrderStatus;
use App\Models\Client;
use App\Models\Product;
use App\Models\Seller;
use App\Models\User;
use App\ValueObjects\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
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

    test('Logged user creates sales order with fractional quantities and receives serialized Money totals', function () {
        $user = User::factory()->create();
        $productA = Product::factory()->for($user->organization)->withStock(10)->create([
            'cost_price' => Money::fromDecimal('5.00'),
            'sale_price' => Money::fromDecimal('10.50'),
        ]);
        $productB = Product::factory()->for($user->organization)->withStock(10)->create([
            'cost_price' => Money::fromDecimal('5.00'),
            'sale_price' => Money::fromDecimal('10.50'),
        ]);
        $client = Client::factory()->for($user->organization)->create();
        $seller = Seller::factory()->for($user->organization)->create();

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_CREATE->value]);

        $response = $this->postJson(route('v1.sales-orders.store'), [
            'client_id' => $client->id,
            'seller_id' => $seller->id,
            'items' => [
                ['product_id' => $productA->id, 'number' => 1, 'quantity' => 0.5],
                ['product_id' => $productB->id, 'number' => 2, 'quantity' => 1.5],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'total' => ['amount', 'currency', 'formatted'],
                    'items' => [
                        '*' => [
                            'unit_price' => ['amount', 'currency', 'formatted'],
                            'subtotal' => ['amount', 'currency', 'formatted'],
                        ],
                    ],
                ],
            ])
            ->assertJsonPath('data.total.amount', '21.0000')
            ->assertJsonPath('data.total.currency', 'BRL')
            ->assertJsonPath('data.items.0.subtotal.amount', '5.2500')
            ->assertJsonPath('data.items.1.subtotal.amount', '15.7500');

        $this->assertDatabaseHas('sales_orders', [
            'id' => $response->json('data.id'),
            'total' => '21.0000',
        ]);
        $this->assertDatabaseHas('sales_order_items', [
            'sales_order_id' => $response->json('data.id'),
            'product_id' => $productA->id,
            'subtotal' => '5.2500',
        ]);
        $this->assertDatabaseHas('sales_order_items', [
            'sales_order_id' => $response->json('data.id'),
            'product_id' => $productB->id,
            'subtotal' => '15.7500',
        ]);
    });

    test('Non logged user', function () {
        $response = $this->postJson(route('v1.sales-orders.store'), []);
        $response->assertUnauthorized();
    });

    test('Logged user without permission', function () {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson(route('v1.sales-orders.store'), []);
        $response->assertForbidden();
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

    test('Logged user with inactive client', function () {
        $user = User::factory()->create();
        $product = Product::factory()->for($user->organization)->withStock(10)->create();
        $client = Client::factory()->for($user->organization)->inactive()->create();
        $seller = Seller::factory()->for($user->organization)->create();

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_CREATE->value]);

        $response = $this->postJson(route('v1.sales-orders.store'), [
            'client_id' => $client->id,
            'seller_id' => $seller->id,
            'items' => [
                ['product_id' => $product->id, 'number' => 1, 'quantity' => 5],
            ],
        ]);

        $response->assertUnprocessable();
        expect($response->json('error.fields.client_id'))->not->toBeNull();
    });

    test('Logged user with inactive seller', function () {
        $user = User::factory()->create();
        $product = Product::factory()->for($user->organization)->withStock(10)->create();
        $client = Client::factory()->for($user->organization)->create();
        $seller = Seller::factory()->for($user->organization)->inactive()->create();

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_CREATE->value]);

        $response = $this->postJson(route('v1.sales-orders.store'), [
            'client_id' => $client->id,
            'seller_id' => $seller->id,
            'items' => [
                ['product_id' => $product->id, 'number' => 1, 'quantity' => 5],
            ],
        ]);

        $response->assertUnprocessable();
        expect($response->json('error.fields.seller_id'))->not->toBeNull();
    });

    test('Logged user with inactive product', function () {
        $user = User::factory()->create();
        $product = Product::factory()->for($user->organization)->withStock(10)->inactive()->create();
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

        $response->assertUnprocessable();
        expect($response->json('error.fields.product_id'))->not->toBeNull();
    });

    test('Logged user fails with 422 for duplicate item number, duplicate product_id, or invalid quantity precision', function (string $scenario) {
        $user = User::factory()->create();
        $product1 = Product::factory()->for($user->organization)->withStock(10)->create();
        $product2 = Product::factory()->for($user->organization)->withStock(10)->create();
        $client = Client::factory()->for($user->organization)->create();
        $seller = Seller::factory()->for($user->organization)->create();

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_CREATE->value]);

        $items = match ($scenario) {
            'duplicate_number' => [
                ['product_id' => $product1->id, 'number' => 1, 'quantity' => 1],
                ['product_id' => $product2->id, 'number' => 1, 'quantity' => 1],
            ],
            'duplicate_product' => [
                ['product_id' => $product1->id, 'number' => 1, 'quantity' => 1],
                ['product_id' => $product1->id, 'number' => 2, 'quantity' => 1],
            ],
            'too_many_decimals' => [
                ['product_id' => $product1->id, 'number' => 1, 'quantity' => 1.1234],
            ],
            'quantity_overflow' => [
                ['product_id' => $product1->id, 'number' => 1, 'quantity' => 10000000],
            ],
        };

        $response = $this->postJson(route('v1.sales-orders.store'), [
            'client_id' => $client->id,
            'seller_id' => $seller->id,
            'items' => $items,
        ]);

        $response->assertUnprocessable();
    })->with(['duplicate_number', 'duplicate_product', 'too_many_decimals', 'quantity_overflow']);
});
