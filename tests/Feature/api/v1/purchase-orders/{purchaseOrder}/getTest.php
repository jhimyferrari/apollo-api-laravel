<?php

use App\Enum\PermissionType;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\Organization;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
describe('GET api/purchase-orders/{purchaseOrder}', function () {
    test('Logged user with valid data', function () {
        $organization = Organization::factory()->create();
        $user = User::factory()->for($organization)->create();
        $purchaseOrder = PurchaseOrder::factory()->for($organization)->create();

        Sanctum::actingAs($user, [PermissionType::PURCHASE_ORDER_READ->value]);

        $response = $this->getJson(
            route('v1.purchase-orders.show', $purchaseOrder)
        );

        $response->assertOk()
            ->assertJson(PurchaseOrderResource::make($purchaseOrder)->response()->getData(true));
    });

    test('Purchase order from another organization', function () {
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();

        $user = User::factory()->for($organization)->create();
        $purchaseOrder = PurchaseOrder::factory()->for($otherOrganization)->create();

        Sanctum::actingAs($user, [PermissionType::PURCHASE_ORDER_READ->value]);

        $response = $this->getJson(
            route('v1.purchase-orders.show', $purchaseOrder)
        );

        $response->assertNotFound();
    });

    test('Non logged user', function () {
        $organization = Organization::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->for($organization)->create();

        $response = $this->getJson(
            route('v1.purchase-orders.show', $purchaseOrder)
        );

        $response->assertUnauthorized();
    });

    test('Logged user without permission', function () {
        $organization = Organization::factory()->create();
        $user = User::factory()->for($organization)->create();
        $purchaseOrder = PurchaseOrder::factory()->for($organization)->create();

        Sanctum::actingAs($user);

        $response = $this->getJson(
            route('v1.purchase-orders.show', $purchaseOrder)
        );

        $response->assertNotFound();
    });
});
