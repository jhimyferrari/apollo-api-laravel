<?php

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
describe('DELETE api/users/{user}', function () {
    test('Logged user with permission', function () {
        $this->assertDatabaseEmpty('users');
        $user = User::factory()->create();
        $secondUser = User::factory()->create([
            'organization_id' => $user->organization_id,
        ]);
        $this->assertDatabaseCount('users', 2);

        Sanctum::actingAs($user, ['user.delete']);
        $response = $this->delete(route('v1.users.destroy', $secondUser));
        $response->assertNoContent();

        $this->assertDatabaseCount('users', 2);
        $this->assertSoftDeleted($secondUser);

        $otherUser = User::factory()->create();

        $response = $this->delete(route('v1.users.destroy', $otherUser));
        $response->assertNotFound();
        $this->assertNotSoftDeleted($otherUser);
    });
    test('Non logged user', function () {
        $user = User::factory()->create();
        $response = $this->deleteJson(route('v1.users.destroy', $user));

        $response->assertUnauthorized();
    });
    test('Logged user without permission', function () {
        $user = User::factory()->create();
        $secondUser = User::factory()->create([
            'organization_id' => $user->organization_id,
        ]);
        Sanctum::actingAs($user);
        $response = $this->deleteJson(route('v1.users.destroy', $secondUser));

        $response->assertForbidden();
    });
    test('User can´t delete himself', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['user.delete']);

        $response = $this->deleteJson(route('v1.users.destroy', $user));

        $response->assertForbidden();
    });
    test('User can´t delete administrator', function () {
        $organization = Organization::factory()->create();
        $admin = User::factory()->create([
            'organization_id' => $organization->id,
            'is_administrator' => 1]);
        $user = User::factory()->create([
            'organization_id' => $organization->id]);
        Sanctum::actingAs($user, ['user.delete']);

        $response = $this->deleteJson(route('v1.users.destroy', $admin));

        $response->assertForbidden();
    });
});
