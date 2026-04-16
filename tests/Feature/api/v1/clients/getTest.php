<?php

use App\Models\Client;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
describe('GET api/clients', function () {
    test('Logged user with valid data', function () {
        $user = User::factory()->create();

        $otherOrganization = Organization::factory()->create();
        Client::factory()->count(5)->create(
            ['organization_id' => $otherOrganization->id]
        );

        Client::factory()->count(20)->create(
            ['organization_id' => $user->organization_id]
        );
        Sanctum::actingAs($user, ['client.show']);

        $response = $this->getJson(route('v1.clients.index'));

        $response->assertOk();
        $response->assertJsonCount(15, 'data');

        $firstRequestData = collect($response->json('data'));
        $ids = $firstRequestData->pluck('organization_id');
        $this->assertNotContains($otherOrganization->id, $ids);
    });
    test('Non logged user', function () {
        $response = $this->getJson(route('v1.clients.index'));
        $response->assertUnauthorized();
    });

    test('Logged user without permission', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $response = $this->getJson(route('v1.clients.index'));
        $response->assertForbidden();
    });
});
