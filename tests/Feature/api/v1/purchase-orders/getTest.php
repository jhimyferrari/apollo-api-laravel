<?php

use App\Enum\PermissionType;
use App\Models\Organization;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
describe('GET api/purchase-orders', function () {
    test('Logged user with valid data', function () {
        $organization = Organization::factory()->create();
        $user = User::factory()->for($organization)->create();

        $otherOrganization = Organization::factory()->create();
        PurchaseOrder::factory()->count(5)->for($otherOrganization)->create();

        PurchaseOrder::factory()->count(20)->for($organization)->create();

        Sanctum::actingAs($user, [PermissionType::PURCHASE_ORDER_READ->value]);

        $response = $this->getJson(route('v1.purchase-orders.index'));

        $response->assertOk();
        $response->assertJsonCount(15, 'data');

        $firstRequestData = collect($response->json('data'));
        $ids = $firstRequestData->pluck('organization_id');
        $this->assertNotContains($otherOrganization->id, $ids);

        $response = $this->getJson(route('v1.purchase-orders.index', ['page' => 2]));
        $response
            ->assertOk()
            ->assertJsonCount(5, 'data');
    });

    test('Non logged user', function () {
        $response = $this->getJson(route('v1.purchase-orders.index'));
        $response->assertUnauthorized();
    });

    test('Logged user without permission', function () {
        Sanctum::actingAs(User::factory()->create());
        $response = $this->getJson(route('v1.purchase-orders.index'));
        $response->assertNotFound();
    });
});
