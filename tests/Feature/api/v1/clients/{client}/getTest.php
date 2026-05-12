<?php

use App\Enum\PermissionType;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
describe('GET api/clients/{client}', function () {
    test('Logged user with permission', function () {
        $user = User::factory()->create();
        $client = Client::factory()->create([
            'organization_id' => $user->organization_id,
        ]);

        Sanctum::actingAs($user, [PermissionType::CLIENT_READ->value]);

        $response = $this->getJson(route('v1.clients.show', $client));

        $response->assertOk()
            ->assertJson(ClientResource::make($client)->response()->getData(true));

        $otherClient = Client::factory()->create();
        $response = $this->getJson(route('v1.clients.show', $otherClient));

        $response->assertNotFound();

    });
    test('Non logged user', function () {
        $client = Client::factory()->create();
        $response = $this->getJson(route('v1.clients.show', $client));

        $response->assertUnauthorized();
    });
    test('Logged user without permission', function () {
        $user = User::factory()->create();
        $client = Client::factory()->create([
        ]);
        Sanctum::actingAs($user);
        $response = $this->getJson(route('v1.clients.show', $client));

        $response->assertNotFound();

        $client = Client::factory()->create(['organization_id' => $user->organization_id]);
        $response = $this->getJson(route('v1.clients.show', $client));

        $response->assertNotFound();

    });
});
