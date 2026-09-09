<?php

use App\Actions\Treatment\TreatAddress;
use App\Exceptions\InvalidFieldException;
use App\Models\Address;
use App\Models\City;
use App\Models\Supplier;
use App\Models\User;
use App\Traits\Service\HandlesAddress;
use Database\Seeders\CitiesSeeder;
use Database\Seeders\UfSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->seed(UfSeeder::class);
    new CitiesSeeder()->run(50);

    $this->handler = new class(app(TreatAddress::class))
    {
        use HandlesAddress;

        public function __construct(private TreatAddress $treatAddress) {}
    };
});

describe('HandlesAddress trait', function () {

    describe('createAddress()', function () {
        it('should treat data and delegate creation to the model', function () {
            $supplier = Supplier::factory()->create(['organization_id' => $this->user->organization_id]);
            $city_ibge_code = City::first()->ibge_code;

            $address = $this->handler->createAddress($supplier, [
                'street' => '  Rua A  ',
                'number' => '213',
                'neighborhood' => 'centro',
                'cep' => '87500000',
                'city_ibge_code' => $city_ibge_code,
            ]);

            expect($address)
                ->toBeInstanceOf(Address::class)
                ->street->toBe('Rua A')
                ->addressable_id->toBe($supplier->id);
        });

        it('should throw when required data is missing (delegated to TreatAddress)', function () {
            $supplier = Supplier::factory()->create(['organization_id' => $this->user->organization_id]);

            expect(fn () => $this->handler->createAddress($supplier, []))
                ->toThrow(InvalidFieldException::class);
        });

        it('should call setDefaultAddress when is_default is true', function () {
            $supplier = Supplier::factory()->create(['organization_id' => $this->user->organization_id]);
            $city_ibge_code = City::first()->ibge_code;
            $oldDefault = $supplier->addresses()->create(
                Address::factory()->turnDefault()->make(['organization_id' => $this->user->organization_id])->toArray()
            );

            $address = $this->handler->createAddress($supplier, [
                'street' => 'Rua A',
                'number' => '213',
                'neighborhood' => 'centro',
                'cep' => '87500000',
                'city_ibge_code' => $city_ibge_code,
                'is_default' => true,
            ]);

            expect($address->fresh())->is_default->toBeTrue();
            expect($oldDefault->fresh())->is_default->toBeFalse();
        });

        it('should not touch other default addresses when is_default is false', function () {
            $supplier = Supplier::factory()->create(['organization_id' => $this->user->organization_id]);
            $city_ibge_code = City::first()->ibge_code;
            $existingDefault = $supplier->addresses()->create(
                Address::factory()->turnDefault()->make(['organization_id' => $this->user->organization_id])->toArray()
            );

            $address = $this->handler->createAddress($supplier, [
                'street' => 'Rua A',
                'number' => '213',
                'neighborhood' => 'centro',
                'cep' => '87500000',
                'city_ibge_code' => $city_ibge_code,
                'is_default' => false,
            ]);

            expect($address->fresh())->is_default->toBeFalse();
            expect($existingDefault->fresh())->is_default->toBeTrue();
        });

        it('should rollback address creation if setDefaultAddress fails inside the transaction', function () {
            $supplier = Supplier::factory()->create(['organization_id' => $this->user->organization_id]);
            $city_ibge_code = City::first()->ibge_code;

            $spy = Mockery::mock($supplier)->makePartial();
            $spy->shouldReceive('setDefaultAddress')->andThrow(new RuntimeException('boom'));

            expect(fn () => $this->handler->createAddress($spy, [
                'street' => 'Rua A',
                'number' => '213',
                'neighborhood' => 'centro',
                'cep' => '87500000',
                'city_ibge_code' => $city_ibge_code,
                'is_default' => true,
            ]))->toThrow(RuntimeException::class);

            $this->assertDatabaseCount('addresses', 0);
        });
    });

    describe('setDefaultAddress()', function () {
        it('should delegate to the model within a transaction', function () {
            $supplier = Supplier::factory()->create(['organization_id' => $this->user->organization_id]);
            $old = $supplier->addresses()->create(
                Address::factory()->turnDefault()->make(['organization_id' => $this->user->organization_id])->toArray()
            );
            $new = $supplier->addresses()->create(
                Address::factory()->make(['organization_id' => $this->user->organization_id])->toArray()
            );

            $this->handler->setDefaultAddress($supplier, $new);

            expect($new->fresh())->is_default->toBeTrue();
            expect($old->fresh())->is_default->toBeFalse();
        });
    });
});
