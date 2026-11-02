<?php

use App\Enum\PermissionType;
use App\Enum\Status\OrderStatus;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('PATCH api/purchase-orders/{purchaseOrder}', function () {
    test('Logged user updates supplier of a draft order', function () {
        $user = User::factory()->create();
        $order = draftOrderFor($user, new PurchaseOrder);
        $newSupplier = Supplier::factory()->for($user->organization)->create();

        Sanctum::actingAs($user, [PermissionType::PURCHASE_ORDER_UPDATE->value]);

        $response = $this->patchJson(route('v1.purchase-orders.update', $order), [
            'supplier_id' => $newSupplier->id,
        ]);

        $response->assertNoContent();

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $order->id,
            'supplier_id' => $newSupplier->id,
        ]);
    });

    test('Logged user adds an item with to_add', function () {
        $user = User::factory()->create();
        $order = draftOrderFor($user, new PurchaseOrder);
        $newProduct = Product::factory()->for($user->organization)->withStock(10)->create();

        Sanctum::actingAs($user, [PermissionType::PURCHASE_ORDER_UPDATE->value]);

        $response = $this->patchJson(route('v1.purchase-orders.update', $order), [
            'items' => [
                'to_add' => [
                    ['product_id' => $newProduct->id, 'number' => 2, 'quantity' => 3],
                ],
            ],
        ]);

        $response->assertNoContent();

        $this->assertDatabaseHas('purchase_order_items', [
            'purchase_order_id' => $order->id,
            'product_id' => $newProduct->id,
            'quantity' => 3,
        ]);
    });

    test('Logged user updates an item quantity with to_update', function () {
        $user = User::factory()->create();
        $order = draftOrderFor($user, new PurchaseOrder);
        $item = $order->items->first();

        Sanctum::actingAs($user, [PermissionType::PURCHASE_ORDER_UPDATE->value]);

        $response = $this->patchJson(route('v1.purchase-orders.update', $order), [
            'items' => [
                'to_update' => [
                    ['id' => $item->id, 'quantity' => 7],
                ],
            ],
        ]);

        $response->assertNoContent();

        $this->assertDatabaseHas('purchase_order_items', [
            'id' => $item->id,
            'quantity' => 7,
        ]);
    });

    test('Logged user removes an item with to_remove', function () {
        $user = User::factory()->create();
        $order = draftOrderFor($user, new PurchaseOrder);
        $item = $order->items->first();
        $otherProduct = Product::factory()->for($user->organization)->withStock(10)->create();

        Sanctum::actingAs($user, [PermissionType::PURCHASE_ORDER_UPDATE->value]);

        // Adiciona um segundo item para a ordem não ficar vazia após a remoção
        $this->patchJson(route('v1.purchase-orders.update', $order), [
            'items' => ['to_add' => [['product_id' => $otherProduct->id, 'number' => 2, 'quantity' => 1]]],
        ])->assertNoContent();

        $response = $this->patchJson(route('v1.purchase-orders.update', $order), [
            'items' => ['to_remove' => [$item->id]],
        ]);

        $response->assertNoContent();

        $this->assertSoftDeleted($item);
    });

    test('Non logged user', function () {
        $order = draftOrderFor(User::factory()->create(), new PurchaseOrder);

        $response = $this->patchJson(route('v1.purchase-orders.update', $order), []);
        $response->assertUnauthorized();
    });

    test('Logged user without permission', function () {
        $user = User::factory()->create();
        $order = draftOrderFor($user, new PurchaseOrder);

        Sanctum::actingAs($user);

        $response = $this->patchJson(route('v1.purchase-orders.update', $order), []);
        $response->assertNotFound();
    });

    test('Logged user cannot update an order from a different organization', function () {
        $user = User::factory()->create();
        $foreignOrder = draftOrderFor(User::factory()->create(), new PurchaseOrder);

        Sanctum::actingAs($user, [PermissionType::PURCHASE_ORDER_UPDATE->value]);

        $response = $this->patchJson(route('v1.purchase-orders.update', $foreignOrder));

        $response->assertNotFound();
    });

    test('Logged user cannot update a confirmed order', function () {
        $user = User::factory()->create();
        $order = draftOrderFor($user, new PurchaseOrder);
        $item = $order->items->first();
        $order->status = OrderStatus::Confirmed;
        $order->save();

        Sanctum::actingAs($user, [PermissionType::PURCHASE_ORDER_UPDATE->value]);

        $response = $this->patchJson(route('v1.purchase-orders.update', $order), [
            'items' => ['to_update' => [['id' => $item->id, 'quantity' => 9]]],
        ]);

        $response->assertUnprocessable();

        $this->assertDatabaseMissing('purchase_order_items', [
            'id' => $item->id,
            'quantity' => 9,
        ]);
    });

    test('Logged user with supplier_id from a different organization', function () {
        $user = User::factory()->create();
        $order = draftOrderFor($user, new PurchaseOrder);
        $otherOrganizationSupplier = Supplier::factory()->create();

        Sanctum::actingAs($user, [PermissionType::PURCHASE_ORDER_UPDATE->value]);

        $response = $this->patchJson(route('v1.purchase-orders.update', $order), [
            'supplier_id' => $otherOrganizationSupplier->id,
        ]);

        $response->assertUnprocessable();
    });

    test('Logged user with negative quantity in to_add', function () {
        $user = User::factory()->create();
        $order = draftOrderFor($user, new PurchaseOrder);
        $product = Product::factory()->for($user->organization)->withStock(10)->create();

        Sanctum::actingAs($user, [PermissionType::PURCHASE_ORDER_UPDATE->value]);

        $response = $this->patchJson(route('v1.purchase-orders.update', $order), [
            'items' => [
                'to_add' => [['product_id' => $product->id, 'number' => 2, 'quantity' => -5]],
            ],
        ]);

        $response->assertUnprocessable();
    });

    test('Logged user with product_id from a different organization in to_add', function () {
        $user = User::factory()->create();
        $order = draftOrderFor($user, new PurchaseOrder);
        $foreignProduct = Product::factory()->withStock(10)->create();

        Sanctum::actingAs($user, [PermissionType::PURCHASE_ORDER_UPDATE->value]);

        $response = $this->patchJson(route('v1.purchase-orders.update', $order), [
            'items' => [
                'to_add' => [['product_id' => $foreignProduct->id, 'number' => 2, 'quantity' => 1]],
            ],
        ]);

        $response->assertUnprocessable();
    });

    test('Logged user with item id from a different order in to_update', function () {
        $user = User::factory()->create();
        $order = draftOrderFor($user, new PurchaseOrder);
        $otherOrder = draftOrderFor($user, new PurchaseOrder);
        $otherItem = $otherOrder->items->first();

        Sanctum::actingAs($user, [PermissionType::PURCHASE_ORDER_UPDATE->value]);

        $response = $this->patchJson(route('v1.purchase-orders.update', $order), [
            'items' => ['to_update' => [['id' => $otherItem->id, 'quantity' => 9]]],
        ]);

        $response->assertUnprocessable();

        $this->assertDatabaseMissing('purchase_order_items', [
            'id' => $otherItem->id,
            'quantity' => 9,
        ]);
    });
});
