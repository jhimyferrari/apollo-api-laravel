<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
describe('DELETE api/clients/{client}', function () {
    test('Logged user with permission', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['client.delete']);
        $client = Client::factory()->create(
            ['organization_id' => $user->organization_id]);
        $response = $this->deleteJson(route('v1.clients.destroy', $client));
        $response->assertNoContent();
        $this->assertSoftDeleted($client);
    });
    test('Logged user without permission', function () {
        $user = User::factory()->create();
        $client = Client::factory()->create();
        Sanctum::actingAs($user);
        $response = $this->deleteJson(route('v1.clients.destroy', $client));
        $response->assertNotFound();

        $client = Client::factory()->create(
            ['organization_id' => $user->organization_id]
        );

        $response = $this->deleteJson(route('v1.clients.destroy', $client));
        $response->assertNotFound();

    });
    test('Non logged user', function () {
        $client = Client::factory()->create();
        $response = $this->deleteJson(route('v1.clients.destroy', $client));
        $response->assertUnauthorized();
    });
});
