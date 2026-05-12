<?php

use App\Enum\PermissionType;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
describe('DELETE api/suppliers/{supplier}', function () {
    test('Logged user with permission', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user, [PermissionType::SUPPLIER_DELETE->value]);
        $supplier = Supplier::factory()->create(
            ['organization_id' => $user->organization_id]);
        $response = $this->deleteJson(route('v1.suppliers.destroy', $supplier));
        $response->assertNoContent();
        $this->assertSoftDeleted($supplier);
    });
    test('Logged user without permission', function () {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create();
        Sanctum::actingAs($user);
        $response = $this->deleteJson(route('v1.suppliers.destroy', $supplier));
        $response->assertNotFound();

        $supplier = Supplier::factory()->create(
            ['organization_id' => $user->organization_id]
        );

        $response = $this->deleteJson(route('v1.suppliers.destroy', $supplier));
        $response->assertNotFound();

    });
    test('Non logged user', function () {
        $supplier = Supplier::factory()->create();
        $response = $this->deleteJson(route('v1.suppliers.destroy', $supplier));
        $response->assertUnauthorized();
    });
});
