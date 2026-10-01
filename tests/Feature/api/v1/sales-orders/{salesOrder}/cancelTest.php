<?php

use App\Enum\PermissionType;
use App\Enum\Status\OrderStatus;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
describe('PATCH api/sales-orders/{salesOrder}/cancel', function () {
    test('Logged user with valid data', function () {
        $user = User::factory()->create();
        $order = SalesOrder::factory()->for($user->organization)->create(['status' => OrderStatus::Draft]);

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_UPDATE->value]);

        $response = $this->patchJson(route('v1.sales-orders.cancel', $order), [
            'reason' => 'Cliente desistiu',
        ]);

        $response->assertNoContent();
    });

    test('Logged user cancelling a confirmed order reverses stock', function () {
        $user = User::factory()->create();
        $product = Product::factory()->for($user->organization)->withStock(15)->create();
        $order = SalesOrder::factory()->for($user->organization)->create(['status' => OrderStatus::Confirmed]);
        $order->items()->create([
            'product_id' => $product->id,
            'number' => 1,
            'quantity' => 5,
            'unit_price' => $product->sale_price,
            'subtotal' => $product->sale_price->multiply(5),
            'organization_id' => $user->organization_id,
        ]);

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_UPDATE->value]);

        $response = $this->patchJson(route('v1.sales-orders.cancel', $order));

        $response->assertNoContent();
        expect($product->fresh()->stock_quantity)->toEqual(20.000);
    });

    test('Non logged user', function () {
        $order = SalesOrder::factory()->create();

        $response = $this->patchJson(route('v1.sales-orders.cancel', $order));
        $response->assertUnauthorized();
    });

    test('Logged user without permission', function () {
        $order = SalesOrder::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $response = $this->patchJson(route('v1.sales-orders.cancel', $order));
        $response->assertNotFound();
    });

    test('Logged user trying to cancel an order from another orgaqualnization', function () {
        $user = User::factory()->create();
        $otherOrganizationOrder = SalesOrder::factory()->create(['status' => OrderStatus::Draft]);

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_UPDATE->value]);

        $response = $this->patchJson(route('v1.sales-orders.cancel', $otherOrganizationOrder));
        $response->assertNotFound();
    });

    test('Logged user trying to cancel an already cancelled order', function () {
        $user = User::factory()->create();
        $order = SalesOrder::factory()->for($user->organization)->create(['status' => OrderStatus::Cancelled]);

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_UPDATE->value]);

        $response = $this->patchJson(route('v1.sales-orders.cancel', $order));
        $response->assertUnprocessable();
    });
});
