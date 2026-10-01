<?php

use App\Enum\PermissionType;
use App\Enum\Status\OrderStatus;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

describe('POST api/sales-orders/{salesOrder}/confirm', function () {
    test('Logged user with valid data', function () {
        $user = User::factory()->create();
        $product = Product::factory()->for($user->organization)->withStock(20)->create();
        $order = SalesOrder::factory()->for($user->organization)->create(['status' => OrderStatus::Draft]);
        $order->items()->create([
            'product_id' => $product->id,
            'number' => 1,
            'quantity' => 5,
            'unit_price' => $product->sale_price,
            'subtotal' => $product->sale_price->multiply(5),
            'organization_id' => $user->organization_id,
        ]);

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_UPDATE->value]);

        $response = $this->patchJson(route('v1.sales-orders.confirm', $order));

        $response->assertNoContent();

        expect($product->fresh()->stock_quantity)->toEqual(15.000);
    });

    test('Non logged user', function () {
        $order = SalesOrder::factory()->create();

        $response = $this->patchJson(route('v1.sales-orders.confirm', $order));
        $response->assertUnauthorized();
    });

    test('Logged user without permission', function () {
        $order = SalesOrder::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $response = $this->patchJson(route('v1.sales-orders.confirm', $order));
        $response->assertNotFound();
    });

    test('Logged user trying to confirm an order from another organization', function () {
        $user = User::factory()->create();
        $otherOrganizationOrder = SalesOrder::factory()->create(['status' => OrderStatus::Draft]);

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_UPDATE->value]);

        $response = $this->patchJson(route('v1.sales-orders.confirm', $otherOrganizationOrder));
        $response->assertNotFound();
    });

    test('Logged user trying to confirm an already confirmed order', function () {
        $user = User::factory()->create();
        $order = SalesOrder::factory()->for($user->organization)->create(['status' => OrderStatus::Confirmed]);

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_UPDATE->value]);

        $response = $this->patchJson(route('v1.sales-orders.confirm', $order));
        $response->assertUnprocessable();
    });

    test('Logged user trying to confirm an order with insufficient stock', function () {
        $user = User::factory()->create();
        $product = Product::factory()->for($user->organization)->withStock(2)->create();
        $order = SalesOrder::factory()->for($user->organization)->create(['status' => OrderStatus::Draft]);
        $order->items()->create([
            'product_id' => $product->id,
            'number' => 1,
            'quantity' => 10,
            'unit_price' => $product->sale_price,
            'subtotal' => $product->sale_price->multiply(10),
            'organization_id' => $user->organization_id,
        ]);

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_UPDATE->value]);

        $response = $this->patchJson(route('v1.sales-orders.confirm', $order));
        $response->assertUnprocessable();

        expect($order->fresh()->status)->toBe(OrderStatus::Draft);
    });
});
