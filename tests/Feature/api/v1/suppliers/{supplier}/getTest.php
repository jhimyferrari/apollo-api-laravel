<?php

use App\Enum\PermissionType;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
describe('GET api/suppliers/{supplier}', function () {
    test('Logged user with permission', function () {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create(['organization_id' => $user->organization_id]);

        Sanctum::actingAs($user, [PermissionType::SUPPLIER_READ->value]);
        $response = $this->getJson(route('v1.suppliers.show', $supplier));

        $response->assertOk()
            ->assertJson(SupplierResource::make($supplier)->response()->getData(true));

        $otherOrganizationSupplier = Supplier::factory()->create();
        $response = $this->getJson(route('v1.suppliers.show', $otherOrganizationSupplier));
        $response->assertNotFound();
    });
    test('Logged user without permission', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $supplier = Supplier::factory()->create(['organization_id' => $user->organization_id]);
        $response = $this->getJson(route('v1.suppliers.show', $supplier));

        $response->assertNotFound();

        $otherOrganizationSupplier = Supplier::factory()->create();
        $response = $this->getJson(route('v1.sellers.show', $otherOrganizationSupplier));
        $response->assertNotFound();
    });
    test('Non logged user', function () {
        $supplier = Supplier::factory()->create();

        $response = $this->getJson(route('v1.suppliers.show', $supplier));

        $response->assertUnauthorized();
    });
});
