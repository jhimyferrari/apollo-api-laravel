<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
describe('PATCH api/clients/{client}', function () {

    test('Logged user with valid data', function () {
        $user = User::factory()->create();
        $client = Client::factory()->create(['organization_id' => $user->organization_id]);
        Sanctum::actingAs($user, ['client.update']);

        $newClientRequest = [
            'document' => fake()->cnpj(false),
            'legal_name' => fake()->domainName(),
            'trade_name' => fake()->name(),
            'state_registration' => fake()->regexify('[0-9]{9,12}'),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->email(),
        ];
        $response = $this->patchJson(route('v1.clients.update', $client), $newClientRequest);

        $response->assertNoContent();
        $this->assertDatabaseHas('clients', $newClientRequest);
    });

    test('Other organization user', function () {
        $user = User::factory()->create();
        $client = Client::factory()->create();
        Sanctum::actingAs($user, ['client.update']);

        $response = $this->patchJson(route('v1.clients.update', $client), []);

        $response->assertNotFound();
    });
    test('Logged user with non valid data', function () {
        $user = User::factory()->create();
        $client = Client::factory()->create();
        Sanctum::actingAs($user, ['client.update']);
        $response = $this->patchJson(route('v1.clients.update', $client), []);

        $response->assertNotFound();
    });
    test('Logged user without permission', function () {
        $client = Client::factory()->create();
        Sanctum::actingAs(User::factory()->create());
        $response = $this->patchJson(route('v1.clients.update', $client), []);

        $response->assertNotFound();
    });
    test('Non logged user', function () {
        $client = Client::factory()->create();
        $response = $this->patchJson(route('v1.clients.update', $client), []);

        $response->assertUnauthorized();
    });
});
