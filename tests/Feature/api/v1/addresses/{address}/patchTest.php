<?php

use App\Enum\PermissionType;
use App\Models\Address;
use App\Models\City;
use App\Models\Client;
use App\Models\Seller;
use App\Models\User;
use Database\Seeders\CitiesSeeder;
use Database\Seeders\UfSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
beforeEach(function () {
    $this->seed(UfSeeder::class);
    new CitiesSeeder()->run(2);
});
describe('PATCH api/addresses/{address}', function () {

    test('Other org model', function () {
        $user = User::factory()->create();
        $client = Client::factory()->create();
        $address = Address::factory()->for($client, 'addressable')->for($client->organization)->create();
        Sanctum::actingAs($user, [PermissionType::CLIENT_UPDATE->value]);

        $response = $this->patchJson(route('v1.addresses.update', $address));

        $response->assertNotFound();
    });
    test('Logged user with invalid data', function () {
        $user = User::factory()->create();
        $client = Client::factory()->for($user->organization)->create();
        $address = Address::factory()->for($client, 'addressable')->for($user->organization)->create();
        Sanctum::actingAs($user, [PermissionType::CLIENT_UPDATE->value]);
        $data = [
            'street' => null,
        ];

        $response = $this->patchJson(route('v1.addresses.update', $address), $data);

        $response->assertUnprocessable();
    });
    test('Logged user with other model permission', function () {
        $user = User::factory()->create();
        $seller = Seller::factory()->for($user->organization)->create();
        $address = Address::factory()->for($seller, 'addressable')->for($user->organization)->create();
        Sanctum::actingAs($user, [PermissionType::CLIENT_UPDATE->value]);

        $response = $this->patchJson(route('v1.addresses.update', $address));

        $response->assertNotFound();

    });
    test('Logged user with right permission', function () {
        $user = User::factory()->create();
        $client = Client::factory()->for($user->organization)->create();
        $address = Address::factory()->for($client, 'addressable')->for($user->organization)->create();
        Sanctum::actingAs($user, [PermissionType::CLIENT_UPDATE->value]);
        $data = [
            'street' => fake()->streetName(),
            'number' => fake()->buildingNumber(),
            'complement' => fake()->optional()->secondaryAddress(),
            'neighborhood' => fake()->word(),
            'city_ibge_code' => City::inRandomOrder()->first()->ibge_code,
            'cep' => fake()->numerify('########'),
        ];

        $response = $this->patchJson(route('v1.addresses.update', $address), $data);

        $response->assertNoContent();

        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'street' => $data['street'],
            'number' => $data['number'],
            'neighborhood' => $data['neighborhood'],
            'city_ibge_code' => $data['city_ibge_code'],
            'cep' => $data['cep'],
        ]);

    });
    test('Logged user without permission', function () {
        $user = User::factory()->create();
        $client = Client::factory()->for($user->organization)->create();
        $address = Address::factory()->for($client, 'addressable')->for($user->organization)->create();
        Sanctum::actingAs($user);
        $response = $this->patchJson(route('v1.addresses.update', $address));

        $response->assertNotFound();
    });
    test('Non logged user', function () {
        $address = Address::factory()->forClient()->create();
        $response = $this->patchJson(route('v1.addresses.update', $address));

        $response->assertUnauthorized();
    });
});
