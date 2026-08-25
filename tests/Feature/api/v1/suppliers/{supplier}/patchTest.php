<?php

use App\Enum\PermissionType;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
describe('PATCH api/suppliers/{supplier}', function () {
    test('Logged user with valid data', function () {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create(['organization_id' => $user->organization_id]);
        Sanctum::actingAs($user, [PermissionType::SUPPLIER_UPDATE->value]);

        $newSupplierRequest = [
            'document' => fake()->cnpj(false),
            'legal_name' => fake()->domainName(),
            'trade_name' => fake()->name(),
            'state_registration' => fake()->regexify('[0-9]{9,12}'),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->email(),
        ];

        $response = $this->patchJson(route('v1.suppliers.update', $supplier), $newSupplierRequest);

        $response->assertNoContent();
        $this->assertDatabaseHas('suppliers', $newSupplierRequest);
    });

    test('Other organization supplier', function () {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create();
        Sanctum::actingAs($user, [PermissionType::SUPPLIER_UPDATE]);

        $response = $this->patchJson(route('v1.suppliers.update', $supplier), []);

        $response->assertNotFound();
    });
    test('Logged user with non valid data', function () {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create(['organization_id' => $user->organization_id]);
        Sanctum::actingAs($user, [PermissionType::SUPPLIER_UPDATE->value]);
        $response = $this->patchJson(route('v1.suppliers.update', $supplier), []);

        $response->assertUnprocessable();
    });
    test('Logged user without permission', function () {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create(['organization_id' => $user->organization_id]);
        Sanctum::actingAs($user);
        $response = $this->patchJson(route('v1.suppliers.update', $supplier), []);

        $response->assertNotFound();

        $otherOrganizationSupplier = Supplier::factory()->create();
        $response = $this->patchJson(route('v1.suppliers.update', $otherOrganizationSupplier), []);
        $response->assertNotFound();

    });
    test('Non logged user', function () {
        $supplier = Supplier::factory()->create();
        $response = $this->patchJson(route('v1.suppliers.update', $supplier), []);

        $response->assertUnauthorized();
    });
});
