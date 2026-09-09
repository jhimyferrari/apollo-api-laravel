<?php

use App\Enum\PermissionType;
use App\Models\Address;
use App\Models\Organization;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\CitiesSeeder;
use Database\Seeders\UfSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('PATCH api/suppliers{supplier}/addresses/{address}/setDefault', function () {

    beforeEach(function () {
        $this->seed(UfSeeder::class);
        new CitiesSeeder()->run(2);

        $this->organization = Organization::factory()->create();
        $this->user = User::factory()->for($this->organization)->create();

        $this->supplier = Supplier::factory()->for($this->organization)->create();

        $this->address = $this->supplier->addresses()->create(Address::factory()->for($this->organization)->make()->toArray());

    });

    test('Logged user with valid data', function () {
        Sanctum::actingAs(
            $this->user,
            [PermissionType::SUPPLIER_UPDATE->value]
        );

        $response = $this->patchJson(route('v1.suppliers.addresses.setDefault', [
            'supplier' => $this->supplier->id,
            'address' => $this->address->id,
        ]));

        $response->assertNoContent();

        expect($this->address->refresh()->is_default)->toBeTrue();
    });

    it('unsets other addresses as default when a new one is set', function () {
        $previousDefault = Address::factory()->turnDefault()
            ->for($this->supplier, 'addressable')->for($this->organization)
            ->create();

        Sanctum::actingAs(
            $this->user,
            [PermissionType::SUPPLIER_UPDATE->value]
        );

        $this->patchJson(route('v1.suppliers.addresses.setDefault', [
            'supplier' => $this->supplier->id,
            'address' => $this->address->id,
        ]))->assertNoContent();

        $previousDefault->refresh();
        $this->address->refresh();
        expect($previousDefault->is_default)->toBeFalse()
            ->and($this->address->is_default)->toBeTrue();
    });

    it('returns 404 when the address does not belong to the supplier', function () {
        $otherSupplier = Supplier::factory()
            ->for($this->organization)
            ->create();

        $foreignAddress = Address::factory()
            ->for($otherSupplier, 'addressable')
            ->for($this->organization)
            ->create();

        Sanctum::actingAs(
            $this->user,
            [PermissionType::SUPPLIER_UPDATE->value]
        );

        $this->patchJson(route('v1.suppliers.addresses.setDefault', [
            'supplier' => $this->supplier->id,
            'address' => $foreignAddress->id,
        ]))->assertNotFound();
    });

    it('returns 404 when the address belongs to a supplier in another organization', function () {

        $otherOrgSupplier = Supplier::factory()->create();

        $otherOrgAddress = Address::factory()
            ->for($otherOrgSupplier, 'addressable')
            ->create();

        Sanctum::actingAs(
            $this->user,
            [PermissionType::SUPPLIER_UPDATE->value]
        );

        $this->patchJson(route('v1.suppliers.addresses.setDefault', [
            'supplier' => $this->supplier->id,
            'address' => $otherOrgAddress->id,
        ]))->assertNotFound();
    });

    test('Logged user withou permission', function () {
        Sanctum::actingAs($this->user, ['some-other-ability']);

        $this->patchJson(route('v1.suppliers.addresses.setDefault', [
            'supplier' => $this->supplier->id,
            'address' => $this->address->id,
        ]))->assertNotFound();
    });

    it('Non logged user', function () {
        $this->patchJson(route('v1.suppliers.addresses.setDefault', [
            'supplier' => $this->supplier->id,
            'address' => $this->address->id,
        ]))->assertUnauthorized();
    });
});
