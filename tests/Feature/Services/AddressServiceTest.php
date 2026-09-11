<?php

use App\Exceptions\InvalidFieldException;
use App\Models\Address;
use App\Models\City;
use App\Models\Client;
use App\Models\Seller;
use App\Models\User;
use App\Services\AddressService;
use Database\Seeders\CitiesSeeder;
use Database\Seeders\UfSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {

    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
    $this->service = app(AddressService::class);

    $this->seed(UfSeeder::class);
    new CitiesSeeder()->run(2);
});
describe('AddressService', function () {
    describe('create', function () {

        it('should create and add an address to an addressable model', function () {
            $seller = Seller::factory()->create(['organization_id' => $this->user->organization_id]);
            $city_ibge_code = City::first()->ibge_code;
            $data = [
                'street' => 'Rua A',
                'number' => '213',
                'neighborhood' => 'centro',
                'cep' => '87500000',
                'is_default' => false,
                'city_ibge_code' => $city_ibge_code,
            ];
            $address = $this->service->create($seller, $data);

            expect($address)
                ->toBeInstanceOf(Address::class)
                ->street->toBe($data['street'])
                ->number->toBe($data['number'])
                ->neighborhood->toBe($data['neighborhood'])
                ->cep->toBe($data['cep'])
                ->is_default->toBeFalse()
                ->city->ibge_code->toBe($city_ibge_code)
                ->addressable->id->toBe($seller->id);
        });

        it('should create add 2 address and change default', function () {
            $seller = Seller::factory()->create(['organization_id' => $this->user->organization_id]);
            $city_ibge_code = City::first()->ibge_code;
            $oldAddress = $seller->addresses()->create(Address::factory()->turnDefault()->make(['organization_id' => $this->user->organization_id])->toArray());
            $data = [
                'street' => 'Rua A',
                'number' => '213',
                'neighborhood' => 'centro',
                'cep' => '87500000',
                'is_default' => true,
                'city_ibge_code' => $city_ibge_code,
            ];
            $address = $this->service->create($seller, $data);

            $this->assertDatabaseCount('addresses', 2);
            expect($address)
                ->toBeInstanceOf(Address::class)
                ->street->toBe($data['street'])
                ->number->toBe($data['number'])
                ->neighborhood->toBe($data['neighborhood'])
                ->cep->toBe($data['cep'])
                ->is_default->toBeTrue()
                ->city->ibge_code->toBe($city_ibge_code)
                ->addressable_type->toBe(Seller::class)
                ->addressable_id->toBe($seller->id);

            $oldAddress->refresh();

            expect($oldAddress)->is_default->toBeFalse();

            expect(
                Address::query()
                    ->where('addressable_id', $seller->id)
                    ->where('is_default', true)
                    ->count()
            )->toBe(1);

            expect($seller->defaultAddress)->id->toBe($address->id);

        });
        it('should throw InvalidFieldException when street is null', function () {
            $seller = Seller::factory()->create(['organization_id' => $this->user->organization_id]);
            $data = [
                'street' => null,
                'number' => '213',
                'neighborhood' => 'centro',
                'cep' => '87500000',
                'city_ibge_code' => City::first()->ibge_code,
            ];

            $this->service->create($seller, $data);
        })->throws(InvalidFieldException::class);

        it('should throw InvalidFieldException when neighborhood is null', function () {
            $seller = Seller::factory()->create(['organization_id' => $this->user->organization_id]);
            $data = [
                'street' => 'Rua A',
                'number' => '213',
                'neighborhood' => null,
                'cep' => '87500000',
                'city_ibge_code' => City::first()->ibge_code,
            ];

            $this->service->create($seller, $data);
        })->throws(InvalidFieldException::class);

        it('should throw InvalidFieldException when number is null', function () {
            $seller = Seller::factory()->create(['organization_id' => $this->user->organization_id]);
            $data = [
                'street' => 'Rua A',
                'number' => null,
                'neighborhood' => 'centro',
                'cep' => '87500000',
                'city_ibge_code' => City::first()->ibge_code,
            ];

            $this->service->create($seller, $data);
        })->throws(InvalidFieldException::class);

        it('should throw InvalidFieldException when cep is null', function () {
            $seller = Seller::factory()->create(['organization_id' => $this->user->organization_id]);
            $data = [
                'street' => 'Rua A',
                'number' => '213',
                'neighborhood' => 'centro',
                'cep' => null,
                'city_ibge_code' => City::first()->ibge_code,
            ];

            $this->service->create($seller, $data);
        })->throws(InvalidFieldException::class);

        it('should throw InvalidFieldException when city_ibge_code is null', function () {
            $seller = Seller::factory()->create(['organization_id' => $this->user->organization_id]);
            $data = [
                'street' => 'Rua A',
                'number' => '213',
                'neighborhood' => 'centro',
                'cep' => '87500000',
                'city_ibge_code' => null,
            ];

            $this->service->create($seller, $data);
        })->throws(InvalidFieldException::class);
    });
    describe('setDefault', function () {

        it('should turn an Address to default', function () {
            $client = Client::factory()->create(['organization_id' => $this->user->organization_id]);
            $address = $client->addresses()->create(Address::factory()->for($this->user->organization)->make()->toArray());
            $this->service->setDefault($client, $address);
            $address->refresh();
            expect($address)->is_default->toBeTrue();
        });

        it('should turn false a default address after turn default other  ', function () {
            $seller = Seller::factory()->create(['organization_id' => $this->user->organization_id]);
            $addressOld = $seller->addresses()->create(Address::factory()->turnDefault()->make(['organization_id' => $this->user->organization_id])->toArray());
            $address = $seller->addresses()->create(Address::factory()->for($this->user->organization)->make()->toArray());

            $this->service->setDefault($seller, $address);
            $address->refresh();
            expect($address)->is_default->toBeTrue();

            $addressOld->refresh();

            expect($addressOld)->is_default->toBeFalse();

        });
    });
    describe('update', function () {

        it('should update address fields', function () {
            $client = Client::factory()->create(['organization_id' => $this->user->organization_id]);
            $address = $client->addresses()->create(
                Address::factory()->for($this->user->organization)->make()->toArray()
            );

            $data = [
                'street' => 'Rua Nova',
                'number' => '999',
                'neighborhood' => 'Bairro Novo',
                'cep' => '87500000',
            ];

            $updated = $this->service->update($address, $data);

            expect($updated)
                ->toBeInstanceOf(Address::class)
                ->street->toBe($data['street'])
                ->number->toBe($data['number'])
                ->neighborhood->toBe($data['neighborhood'])
                ->cep->toBe($data['cep']);
        });

        it('should update only the provided fields, leaving the rest untouched', function () {
            $client = Client::factory()->create(['organization_id' => $this->user->organization_id]);
            $address = $client->addresses()->create(
                Address::factory()->for($this->user->organization)->make(['street' => 'Rua Antiga'])->toArray()
            );

            $updated = $this->service->update($address, ['number' => '321']);

            expect($updated)
                ->number->toBe('321')
                ->street->toBe('Rua Antiga');
        });

        it('should update the city via city_ibge_code', function () {
            $client = Client::factory()->create(['organization_id' => $this->user->organization_id]);
            $address = $client->addresses()->create(
                Address::factory()->for($this->user->organization)->make()->toArray()
            );

            $newCity = City::query()->where('ibge_code', '!=', $address->city_ibge_code)->first();

            $updated = $this->service->update($address, ['city_ibge_code' => $newCity->ibge_code]);

            expect($updated)->city->ibge_code->toBe($newCity->ibge_code);
        });

        it('should set address as default when is_default is passed as true', function () {
            $seller = Seller::factory()->create(['organization_id' => $this->user->organization_id]);
            $oldDefault = $seller->addresses()->create(
                Address::factory()->turnDefault()->make(['organization_id' => $this->user->organization_id])->toArray()
            );
            $address = $seller->addresses()->create(
                Address::factory()->for($this->user->organization)->make()->toArray()
            );

            $this->service->update($address, ['is_default' => true]);

            $address->refresh();
            $oldDefault->refresh();

            expect($address)->is_default->toBeTrue();
            expect($oldDefault)->is_default->toBeFalse();
        });
        it('should throw InvalidFieldException when street is set to null', function () {
            $client = Client::factory()->create(['organization_id' => $this->user->organization_id]);
            $address = $client->addresses()->create(
                Address::factory()->for($this->user->organization)->make()->toArray()
            );

            $this->service->update($address, ['street' => null]);
        })->throws(InvalidFieldException::class);

        it('should throw InvalidFieldException when neighborhood is set to null', function () {
            $client = Client::factory()->create(['organization_id' => $this->user->organization_id]);
            $address = $client->addresses()->create(
                Address::factory()->for($this->user->organization)->make()->toArray()
            );

            $this->service->update($address, ['neighborhood' => null]);
        })->throws(InvalidFieldException::class);

        it('should throw InvalidFieldException when number is set to null', function () {
            $client = Client::factory()->create(['organization_id' => $this->user->organization_id]);
            $address = $client->addresses()->create(
                Address::factory()->for($this->user->organization)->make()->toArray()
            );

            $this->service->update($address, ['number' => null]);
        })->throws(InvalidFieldException::class);

        it('should throw InvalidFieldException when cep is set to null', function () {
            $client = Client::factory()->create(['organization_id' => $this->user->organization_id]);
            $address = $client->addresses()->create(
                Address::factory()->for($this->user->organization)->make()->toArray()
            );

            $this->service->update($address, ['cep' => null]);
        })->throws(InvalidFieldException::class);

        it('should throw InvalidFieldException when city_ibge_code is set to null', function () {
            $client = Client::factory()->create(['organization_id' => $this->user->organization_id]);
            $address = $client->addresses()->create(
                Address::factory()->for($this->user->organization)->make()->toArray()
            );

            $this->service->update($address, ['city_ibge_code' => null]);
        })->throws(InvalidFieldException::class);

    });
    describe('delete', function () {
        it('must softdelete some address', function () {
            $address = Address::factory()->forClient()->create();
            $this->service->delete($address);

            $this->assertSoftDeleted($address);
        });
    });
});
