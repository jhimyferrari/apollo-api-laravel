<?php

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);
describe('GET api/users', function () {
    test('Logged user', function () {
        $this->assertDatabaseEmpty('users');
        $this->assertDatabaseEmpty('organizations');

        $organization = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $organization->id,
        ]);
        User::factory()->count(20)->create([
            'organization_id' => $organization->id,
        ]);

        $this->assertDatabaseCount('users', 21);
        $this->assertDatabaseCount('organizations', 1);

        $otherOrganization = Organization::factory()->create();
        User::factory()->count(5)->create([
            'organization_id' => $otherOrganization->id,
        ]);

        Sanctum::actingAs($user, ['user.view']);
        $response = $this->getJson(route('v1.users.index'));
        $response->assertOk();
        $response->assertJsonCount(15, 'data');

        $firstRequestData = collect($response->json('data'));
        $ids = $firstRequestData->pluck('organization_id');
        $this->assertNotContains($otherOrganization->id, $ids);

        $response = $this->getJson(route('v1.users.index', ['page' => 2]));
        $response->assertOk();
        $response->assertJsonCount(6, 'data');

        $this->assertNotContains($response->json('data'), $firstRequestData);
    });
    test('Non logged user', function () {
        $response = getJson(route('v1.users.index'));
        $response->assertUnauthorized();
    });
    test('Logged user without permission', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $response = getJson(route('v1.users.index'));
        $response->assertForbidden();
    });
});
