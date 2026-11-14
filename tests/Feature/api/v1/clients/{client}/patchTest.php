<?php

use App\Enum\PermissionType;
use App\Enum\Status\ClientStatus;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
describe('PATCH api/clients/{client}', function () {

    test('Logged user with valid data', function () {
        $user = User::factory()->create();
        $client = Client::factory()->create(['organization_id' => $user->organization_id]);
        Sanctum::actingAs($user, [PermissionType::CLIENT_UPDATE->value]);

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

    test('Logged user can update client status', function () {
        $user = User::factory()->create();
        $client = Client::factory()->create([
            'organization_id' => $user->organization_id,
            'status' => ClientStatus::Active,
        ]);
        Sanctum::actingAs($user, [PermissionType::CLIENT_UPDATE->value]);

        $payload = [
            'document' => fake()->cnpj(false),
            'legal_name' => fake()->company(),
            'trade_name' => fake()->company(),
            'status' => ClientStatus::Inactive->value,
        ];
        $response = $this->patchJson(route('v1.clients.update', $client), $payload);

        $response->assertNoContent();
        expect($client->fresh()->status)->toBe(ClientStatus::Inactive);
    });

    test('Logged user fails to update client with invalid status', function () {
        $user = User::factory()->create();
        $client = Client::factory()->create(['organization_id' => $user->organization_id]);
        Sanctum::actingAs($user, [PermissionType::CLIENT_UPDATE->value]);

        $payload = [
            'document' => fake()->cnpj(false),
            'legal_name' => fake()->company(),
            'trade_name' => fake()->company(),
            'status' => 'invalid_status',
        ];
        $response = $this->patchJson(route('v1.clients.update', $client), $payload);

        $response->assertUnprocessable()
            ->assertJsonStructure(['error' => ['fields' => ['status']]]);
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
        $client = Client::factory()->create(['organization_id' => $user->organization_id]);
        Sanctum::actingAs($user, [PermissionType::CLIENT_UPDATE->value]);
        $response = $this->patchJson(route('v1.clients.update', $client), []);

        $response->assertUnprocessable();
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
