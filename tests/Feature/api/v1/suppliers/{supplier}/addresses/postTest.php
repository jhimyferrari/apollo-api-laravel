<?php

use App\Enum\PermissionType;
use App\Models\City;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\CitiesSeeder;
use Database\Seeders\UfSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(UfSeeder::class);
    new CitiesSeeder()->run(50);
});

describe('POST api/suppliers/{supplier}/address', function () {
    test('Logged user with valid data', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user, [PermissionType::SUPPLIER_UPDATE->value]);

        $supplier = Supplier::factory()->create(['organization_id' => $user->organization_id]);
        $city_ibge_code = City::first()->ibge_code;

        $data = [
            'street' => fake()->streetName,
            'number' => fake()->buildingNumber,
            'neighborhood' => fake()->citySuffix,
            'cep' => fake()->numerify('########'),
            'city_ibge_code' => $city_ibge_code,
        ];

        $response = $this->postJson(route('v1.suppliers.addresses.store', $supplier), $data);

        $response->assertCreated();
        expect($response->json('data'))
            ->street->toBe($data['street'])
            ->number->toBe($data['number'])
            ->neighborhood->toBe($data['neighborhood']);
    });

    test('Logged user with invalid data', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user, [PermissionType::SUPPLIER_UPDATE->value]);
        $supplier = Supplier::factory()->create(['organization_id' => $user->organization_id]);

        $response = $this->postJson(route('v1.suppliers.addresses.store', $supplier), []);

        $response->assertUnprocessable();
    });

    test('Logged user with non existent city_ibge_code', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user, [PermissionType::SUPPLIER_UPDATE->value]);
        $supplier = Supplier::factory()->create(['organization_id' => $user->organization_id]);

        $data = [
            'street' => fake()->streetName,
            'number' => fake()->buildingNumber,
            'neighborhood' => fake()->citySuffix,
            'cep' => fake()->numerify('########'),
            'city_ibge_code' => '9999999',
        ];

        $response = $this->postJson(route('v1.suppliers.addresses.store', $supplier), $data);

        $response->assertUnprocessable();
    });
    test('Logged user with other organization supplier', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user, [PermissionType::SUPPLIER_UPDATE->value]);

        $supplier = Supplier::factory()->create();
        $city_ibge_code = City::first()->ibge_code;

        $data = [
            'street' => fake()->streetName,
            'number' => fake()->buildingNumber,
            'neighborhood' => fake()->citySuffix,
            'cep' => fake()->numerify('########'),
            'city_ibge_code' => $city_ibge_code,
        ];

        $response = $this->postJson(route('v1.suppliers.addresses.store', $supplier), $data);

        $response->assertNotFound();
    });

    test('Logged user without permission', function () {

        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $supplier = Supplier::factory()->create(['organization_id' => $user->organization_id]);

        $response = $this->postJson(route('v1.suppliers.addresses.store', $supplier), []);

        $response->assertNotFound();
    });

    test('Non logged user', function () {
        $supplier = Supplier::factory()->create();

        $response = $this->postJson(route('v1.suppliers.addresses.store', $supplier), []);

        $response->assertUnauthorized();
    });

    test('Logged user with valid data for non existent supplier', function () {
        Sanctum::actingAs(User::factory()->create(), [PermissionType::SUPPLIER_UPDATE->value]);
        $response = $this->postJson(route('v1.suppliers.addresses.store', ['supplier' => 'non-existent-id']), []);

        $response->assertNotFound();
    });
});
