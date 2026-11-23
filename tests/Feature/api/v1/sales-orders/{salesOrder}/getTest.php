<?php

use App\Enum\PermissionType;
use App\Http\Resources\SalesOrderResource;
use App\Models\Organization;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\User;
use App\ValueObjects\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
describe('GET api/sales-orders/{salesOrder}', function () {
    test('Logged user with valid data', function () {
        $organization = Organization::factory()->create();
        $user = User::factory()->for($organization)->create();
        $product = Product::factory()->for($organization)->create([
            'cost_price' => Money::fromDecimal('10.00'),
            'sale_price' => Money::fromDecimal('19.90'),
        ]);
        $salesOrder = SalesOrder::factory()->for($organization)->create([
            'total' => Money::fromDecimal('39.80'),
        ]);
        $salesOrder->items()->create([
            'organization_id' => $organization->id,
            'product_id' => $product->id,
            'number' => 1,
            'quantity' => 2,
            'unit_price' => Money::fromDecimal('19.90'),
            'subtotal' => Money::fromDecimal('39.80'),
        ]);

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_READ->value]);

        $response = $this->getJson(
            route('v1.sales-orders.show', $salesOrder)
        );

        $response->assertOk()
            ->assertJson(SalesOrderResource::make($salesOrder->load(['client', 'seller', 'items.product']))->response()->getData(true))
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
            ->assertJsonPath('data.total.amount', '39.8000')
            ->assertJsonPath('data.items.0.unit_price.amount', '19.9000')
            ->assertJsonPath('data.items.0.subtotal.amount', '39.8000');
    });

    test('Sales order from another organization', function () {
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();

        $user = User::factory()->for($organization)->create();
        $salesOrder = SalesOrder::factory()->for($otherOrganization)->create();

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_READ->value]);

        $response = $this->getJson(
            route('v1.sales-orders.show', $salesOrder)
        );

        $response->assertNotFound();
    });

    test('Non logged user', function () {
        $organization = Organization::factory()->create();
        $salesOrder = SalesOrder::factory()->for($organization)->create();

        $response = $this->getJson(
            route('v1.sales-orders.show', $salesOrder)
        );

        $response->assertUnauthorized();
    });

    test('Logged user without permission', function () {
        $organization = Organization::factory()->create();
        $user = User::factory()->for($organization)->create();
        $salesOrder = SalesOrder::factory()->for($organization)->create();

        Sanctum::actingAs($user);

        $response = $this->getJson(
            route('v1.sales-orders.show', $salesOrder)
        );

        $response->assertForbidden();
    });
});
