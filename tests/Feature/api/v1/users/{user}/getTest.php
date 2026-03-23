<?php

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
describe('GET api/users/{user}', function () {
    test('Valid user with valid data', function () {
        $organization = Organization::factory()->create();
        [$firtsUser, $secondUser] = User::factory()->count(2)->create([
            'organization_id' => $organization->id,
        ]);

        Sanctum::actingAs($firtsUser, ['user.view']);
        $response = $this->getJson(route('v1.users.show', $secondUser));

        $response->assertOk();
        $response->assertJson([
            'id' => $secondUser->id,
            'email' => $secondUser->email,
            'name' => $secondUser->name,
        ]);
        $response->assertJsonMissingPath('password');

        $otherOrganization = Organization::factory()->create();
        $otherUser = User::factory()->create(
            ['organization_id' => $otherOrganization->id]
        );
        $response = $this->getJson(route('v1.users.show', $otherUser));

        $response->assertNotFound();
    });
    test('Non logged user', function () {
        $user = User::factory()->create();
        $response = $this->getJson(route('v1.users.show', $user));
        $response->assertUnauthorized();
    });
    test('Logged user without permission', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $response = $this->getJson(route('v1.users.show', $user));
        $response->assertForbidden();
    });
});
