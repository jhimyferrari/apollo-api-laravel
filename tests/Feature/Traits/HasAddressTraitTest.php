<?php

use App\Models\Address;
use App\Models\City;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\CitiesSeeder;
use Database\Seeders\UfSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->seed(UfSeeder::class);
    new CitiesSeeder()->run(50);
});

describe('HasAddresses trait', function () {

    describe('addresses()', function () {
        it('should return a MorphMany relation scoped to the model', function () {
            $supplier = Supplier::factory()->create(['organization_id' => $this->user->organization_id]);
            $other = Supplier::factory()->create(['organization_id' => $this->user->organization_id]);

            $supplier->addresses()->create(
                Address::factory()->make(['organization_id' => $this->user->organization_id])->toArray()
            );
            $other->addresses()->create(
                Address::factory()->make(['organization_id' => $this->user->organization_id])->toArray()
            );

            expect($supplier->addresses)->toHaveCount(1);
            expect($other->addresses)->toHaveCount(1);
        });
    });

    describe('defaultAddress()', function () {
        it('should return only the address marked as default', function () {
            $supplier = Supplier::factory()->create(['organization_id' => $this->user->organization_id]);
            $supplier->addresses()->create(
                Address::factory()->make(['organization_id' => $this->user->organization_id, 'is_default' => false])->toArray()
            );
            $default = $supplier->addresses()->create(
                Address::factory()->make(['organization_id' => $this->user->organization_id, 'is_default' => true])->toArray()
            );

            expect($supplier->defaultAddress)->id->toBe($default->id);
        });

        it('should return null when there is no default address', function () {
            $supplier = Supplier::factory()->create(['organization_id' => $this->user->organization_id]);
            $supplier->addresses()->create(
                Address::factory()->make(['organization_id' => $this->user->organization_id, 'is_default' => false])->toArray()
            );

            expect($supplier->defaultAddress)->toBeNull();
        });
    });

    describe('addAddress()', function () {
        it('should create an address and force organization_id from the model', function () {
            $supplier = Supplier::factory()->create(['organization_id' => $this->user->organization_id]);
            $city_ibge_code = City::first()->ibge_code;

            $address = $supplier->addAddress([
                'street' => 'Rua A',
                'number' => '213',
                'neighborhood' => 'centro',
                'cep' => '87500000',
                'city_ibge_code' => $city_ibge_code,
                'complement' => null,
                'is_default' => false,
            ]);

            expect($address)
                ->toBeInstanceOf(Address::class)
                ->organization_id->toBe($supplier->organization_id)
                ->addressable_id->toBe($supplier->id)
                ->addressable_type->toBe(Supplier::class);
        });

        it('should override any organization_id passed in data with the model organization_id', function () {
            $supplier = Supplier::factory()->create(['organization_id' => $this->user->organization_id]);
            $city_ibge_code = City::first()->ibge_code;

            $address = $supplier->addAddress([
                'street' => 'Rua A',
                'number' => '213',
                'neighborhood' => 'centro',
                'cep' => '87500000',
                'city_ibge_code' => $city_ibge_code,
                'organization_id' => 'fake-org-id',
            ]);

            expect($address->organization_id)->toBe($supplier->organization_id);
        });
    });

    describe('deleteAddress()', function () {
        it('should delete an address belonging to the model', function () {
            $supplier = Supplier::factory()->create(['organization_id' => $this->user->organization_id]);
            $address = $supplier->addresses()->create(
                Address::factory()->make(['organization_id' => $this->user->organization_id])->toArray()
            );

            $supplier->deleteAddress($address);

            $address->refresh();

            $this->assertSoftDeleted($address);
        });

        it('should throw when trying to delete an address that belongs to another model', function () {
            $supplier = Supplier::factory()->create(['organization_id' => $this->user->organization_id]);
            $other = Supplier::factory()->create(['organization_id' => $this->user->organization_id]);
            $address = $other->addresses()->create(
                Address::factory()->make(['organization_id' => $this->user->organization_id])->toArray()
            );

            expect(fn () => $supplier->deleteAddress($address))
                ->toThrow(ModelNotFoundException::class);

            $address->refresh();

            $this->assertNotSoftDeleted($address);
        });
    });

    describe('setDefaultAddress()', function () {
        it('should set the given address as default and unset the others', function () {
            $supplier = Supplier::factory()->create(['organization_id' => $this->user->organization_id]);
            $old = $supplier->addresses()->create(
                Address::factory()->turnDefault()->make(['organization_id' => $this->user->organization_id])->toArray()
            );
            $new = $supplier->addresses()->create(
                Address::factory()->make(['organization_id' => $this->user->organization_id])->toArray()
            );

            $supplier->setDefaultAddress($new);

            expect($new->fresh())->is_default->toBeTrue();
            expect($old->fresh())->is_default->toBeFalse();
        });

        it('should not affect default addresses of other models', function () {
            $supplier = Supplier::factory()->create(['organization_id' => $this->user->organization_id]);
            $other = Supplier::factory()->create(['organization_id' => $this->user->organization_id]);

            $otherDefault = $other->addresses()->create(
                Address::factory()->turnDefault()->make(['organization_id' => $this->user->organization_id])->toArray()
            );
            $address = $supplier->addresses()->create(
                Address::factory()->make(['organization_id' => $this->user->organization_id])->toArray()
            );

            $supplier->setDefaultAddress($address);

            expect($otherDefault->fresh())->is_default->toBeTrue();
        });
    });
});
