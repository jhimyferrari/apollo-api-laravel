<?php

use App\Enum\PermissionType;
use App\Http\Resources\SalesOrderResource;
use App\Models\Organization;
use App\Models\SalesOrder;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

describe('GET api/sales-orders/{salesOrder}', function () {
    test('Logged user with valid data', function () {
        $organization = Organization::factory()->create();
        $user = User::factory()->for($organization)->create();
        $salesOrder = SalesOrder::factory()->for($organization)->create();

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_READ->value]);

        $response = $this->getJson(
            route('v1.sales-orders.show', $salesOrder)
        );

        $response->assertOk()
            ->assertJson(SalesOrderResource::make($salesOrder)->response()->getData(true));
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

        $response->assertNotFound();
    });
});
