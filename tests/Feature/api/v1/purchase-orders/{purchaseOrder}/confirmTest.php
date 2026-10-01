<?php

use App\Enum\PermissionType;
use App\Enum\Status\OrderStatus;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
describe('POST api/purchase-orders/{purchaseOrder}/confirm', function () {
    test('Logged user with valid data', function () {
        $user = User::factory()->create();
        $product = Product::factory()->for($user->organization)->withStock(20)->create();
        $order = PurchaseOrder::factory()->for($user->organization)->create(['status' => OrderStatus::Draft]);
        $order->items()->create([
            'product_id' => $product->id,
            'number' => 1,
            'quantity' => 5,
            'unit_price' => $product->sale_price,
            'subtotal' => $product->sale_price->multiply(5),
            'organization_id' => $user->organization_id,
        ]);

        Sanctum::actingAs($user, [PermissionType::PURCHASE_ORDER_UPDATE->value]);

        $response = $this->patchJson(route('v1.purchase-orders.confirm', $order));

        $response->assertNoContent();

        expect($product->fresh()->stock_quantity)->toEqual(15.000);
    });

    test('Non logged user', function () {
        $order = PurchaseOrder::factory()->create();

        $response = $this->patchJson(route('v1.purchase-orders.confirm', $order));
        $response->assertUnauthorized();
    });

    test('Logged user without permission', function () {
        $order = PurchaseOrder::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $response = $this->patchJson(route('v1.purchase-orders.confirm', $order));
        $response->assertNotFound();
    });

    test('Logged user trying to confirm an order from another organization', function () {
        $user = User::factory()->create();
        $otherOrganizationOrder = PurchaseOrder::factory()->create(['status' => OrderStatus::Draft]);

        Sanctum::actingAs($user, [PermissionType::PURCHASE_ORDER_UPDATE->value]);

        $response = $this->patchJson(route('v1.purchase-orders.confirm', $otherOrganizationOrder));
        $response->assertNotFound();
    });

    test('Logged user trying to confirm an already confirmed order', function () {
        $user = User::factory()->create();
        $order = PurchaseOrder::factory()->for($user->organization)->create(['status' => OrderStatus::Confirmed]);

        Sanctum::actingAs($user, [PermissionType::PURCHASE_ORDER_UPDATE->value]);

        $response = $this->patchJson(route('v1.purchase-orders.confirm', $order));
        $response->assertUnprocessable();
    });

    test('Logged user trying to confirm an order with insufficient stock', function () {
        $user = User::factory()->create();
        $product = Product::factory()->for($user->organization)->withStock(2)->create();
        $order = PurchaseOrder::factory()->for($user->organization)->create(['status' => OrderStatus::Draft]);
        $order->items()->create([
            'product_id' => $product->id,
            'number' => 1,
            'quantity' => 10,
            'unit_price' => $product->sale_price,
            'subtotal' => $product->sale_price->multiply(10),
            'organization_id' => $user->organization_id,
        ]);

        Sanctum::actingAs($user, [PermissionType::PURCHASE_ORDER_UPDATE->value]);

        $response = $this->patchJson(route('v1.purchase-orders.confirm', $order));
        $response->assertUnprocessable();

        expect($order->fresh()->status)->toBe(OrderStatus::Draft);
    });
});
