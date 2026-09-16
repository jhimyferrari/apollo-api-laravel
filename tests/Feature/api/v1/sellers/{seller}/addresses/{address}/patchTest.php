<?php

use App\Enum\PermissionType;
use App\Models\Address;
use App\Models\Organization;
use App\Models\Seller;
use App\Models\User;
use Database\Seeders\CitiesSeeder;
use Database\Seeders\UfSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('PATCH api/sellers/{seller}/addresses/{address}/setDefault', function () {

    beforeEach(function () {
        $this->seed(UfSeeder::class);
        new CitiesSeeder()->run(2);

        $this->organization = Organization::factory()->create();
        $this->user = User::factory()->for($this->organization)->create();

        $this->seller = Seller::factory()->for($this->organization)->create();

        $this->address = $this->seller->addresses()->create(Address::factory()->for($this->organization)->make()->toArray());

    });

    test('Logged user with valid data', function () {
        Sanctum::actingAs(
            $this->user,
            [PermissionType::SELLER_UPDATE->value]
        );

        $response = $this->patchJson(route('v1.sellers.addresses.setDefault', [
            'seller' => $this->seller->id,
            'address' => $this->address->id,
        ]));

        $response->assertNoContent();

        expect($this->address->refresh()->is_default)->toBeTrue();
    });

    it('unsets other addresses as default when a new one is set', function () {
        $previousDefault = Address::factory()->turnDefault()
            ->for($this->seller, 'addressable')->for($this->organization)
            ->create();

        Sanctum::actingAs(
            $this->user,
            [PermissionType::SELLER_UPDATE->value]
        );

        $this->patchJson(route('v1.sellers.addresses.setDefault', [
            'seller' => $this->seller->id,
            'address' => $this->address->id,
        ]))->assertNoContent();

        $previousDefault->refresh();
        $this->address->refresh();
        expect($previousDefault->is_default)->toBeFalse()
            ->and($this->address->is_default)->toBeTrue();
    });

    it('returns 404 when the address does not belong to the seller', function () {
        $otherSeller = Seller::factory()
            ->for($this->organization)
            ->create();

        $foreignAddress = Address::factory()
            ->for($otherSeller, 'addressable')
            ->for($this->organization)
            ->create();

        Sanctum::actingAs(
            $this->user,
            [PermissionType::SELLER_UPDATE->value]
        );

        $this->patchJson(route('v1.sellers.addresses.setDefault', [
            'seller' => $this->seller->id,
            'address' => $foreignAddress->id,
        ]))->assertNotFound();
    });

    it('returns 404 when the address belongs to a seller in another organization', function () {

        $otherOrgSeller = Seller::factory()->create();

        $otherOrgAddress = Address::factory()
            ->for($otherOrgSeller, 'addressable')
            ->create();

        Sanctum::actingAs(
            $this->user,
            [PermissionType::SELLER_UPDATE->value]
        );

        $this->patchJson(route('v1.sellers.addresses.setDefault', [
            'seller' => $this->seller->id,
            'address' => $otherOrgAddress->id,
        ]))->assertNotFound();
    });

    test('Logged user withou permission', function () {
        Sanctum::actingAs($this->user, ['some-other-ability']);

        $this->patchJson(route('v1.sellers.addresses.setDefault', [
            'seller' => $this->seller->id,
            'address' => $this->address->id,
        ]))->assertNotFound();
    });

    it('Non logged user', function () {
        $this->patchJson(route('v1.sellers.addresses.setDefault', [
            'seller' => $this->seller->id,
            'address' => $this->address->id,
        ]))->assertUnauthorized();
    });
});
