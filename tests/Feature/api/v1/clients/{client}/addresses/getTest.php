<?php

use App\Enum\PermissionType;
use App\Models\Address;
use App\Models\Client;
use App\Models\User;
use Database\Seeders\CitiesSeeder;
use Database\Seeders\UfSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
describe('GET api/clients/{client}/address', function () {
    test('Logged user with valid data', function () {
        $this->seed(UfSeeder::class);
        new CitiesSeeder()->run(2);

        $user = User::factory()->create();
        $client = Client::factory()->for($user->organization)->create();

        Sanctum::actingAs($user, [PermissionType::CLIENT_READ->value]);

        $addresses1 = Address::factory()->for($client, 'addressable')->for($user->organization)->count(5)->create();
        $addresses2 = Address::factory()->for($client, 'addressable')->for($user->organization)->count(5)->create();

        $response = $this->getJson(route('v1.clients.addresses.show', $client));

        $response->assertOk();
        $response->assertJsonCount(5, 'data');

        $data = collect($response->json('data'));
        expect($data->pluck('id'))->toContain(...$addresses1->pluck('id'));

        $response = $this->getJson(route('v1.clients.addresses.show', ['page' => 2, 'client' => $client]));
        $response->assertOk();
        $response->assertJsonCount(5, 'data');
        $data = collect($response->json('data'));
        expect($data->pluck('id'))->toContain(...$addresses2->pluck('id'));
    });
    test('Client without address', function () {
        $user = User::factory()->create();
        $client = Client::factory()->for($user->organization)->create();
        Sanctum::actingAs($user, [PermissionType::CLIENT_READ->value]);

        $response = $this->getJson(route('v1.clients.addresses.show', $client));

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    });
    test('Other org client', function () {
        $user = User::factory()->create();
        $client = Client::factory()->create();
        Sanctum::actingAs($user, [PermissionType::CLIENT_READ->value]);
        $this->getJson(route('v1.clients.addresses.show', $client))
            ->assertNotFound();
    });
    test('Logged user without permission', function () {
        $user = User::factory()->create();
        $client = Client::factory()->for($user->organization)->create();
        Sanctum::actingAs($user);
        $this->getJson(route('v1.clients.addresses.show', $client))
            ->assertNotFound();
    });
    test('Non logged user', function () {
        $client = Client::factory()->create();
        $this->getJson(route('v1.clients.addresses.show', $client))
            ->assertUnauthorized();
    });
});
