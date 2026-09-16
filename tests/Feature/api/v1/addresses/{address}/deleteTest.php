<?php

use App\Enum\PermissionType;
use App\Models\Address;
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
describe('DELETE api/addresses/{address}', function () {

    test('Other org model', function () {
        $user = User::factory()->create();
        $client = Client::factory()->create();
        $address = Address::factory()->for($client, 'addressable')->for($client->organization)->create();
        Sanctum::actingAs($user, [PermissionType::CLIENT_DELETE->value]);

        $response = $this->deleteJson(route('v1.addresses.destroy', $address));

        $response->assertNotFound();

    });
    test('Logger user with other model permission', function () {
        $user = User::factory()->create();
        $seller = Seller::factory()->for($user->organization)->create();
        $address = Address::factory()->for($seller, 'addressable')->for($user->organization)->create();
        Sanctum::actingAs($user, [PermissionType::CLIENT_DELETE->value]);

        $response = $this->deleteJson(route('v1.addresses.destroy', $address));

        $response->assertNotFound();

    });
    test('Logged user with right permission', function () {
        $user = User::factory()->create();
        $client = Client::factory()->for($user->organization)->create();
        $address = Address::factory()->for($client, 'addressable')->for($user->organization)->create();
        Sanctum::actingAs($user, [PermissionType::CLIENT_DELETE->value]);

        $response = $this->deleteJson(route('v1.addresses.destroy', $address));

        $response->assertNoContent();
        $this->assertSoftDeleted($address);

    });
    test('Logged user without permission', function () {
        $user = User::factory()->create();
        $client = Client::factory()->for($user->organization)->create();
        $address = Address::factory()->for($client, 'addressable')->for($user->organization)->create();
        Sanctum::actingAs($user);
        $response = $this->deleteJson(route('v1.addresses.destroy', $address));

        $response->assertNotFound();
    });
    test('Non logged user', function () {
        $address = Address::factory()->forClient()->create();
        $response = $this->deleteJson(route('v1.addresses.destroy', $address));

        $response->assertUnauthorized();
    });
});
