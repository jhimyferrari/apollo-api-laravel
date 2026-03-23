<?php

use App\Models\Organization;
use App\Models\Permission;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
describe('PATCH api/users/{user}', function () {

    test('Logged user with valid data', function () {
        $this->seed(PermissionSeeder::class);

        $organization = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $organization->id,
        ]);
        $secondUser = User::factory()->create([
            'organization_id' => $organization->id,
        ]);

        Sanctum::actingAs($user, ['user.update']);
        $permissions = Permission::where('name', 'like', 'user.%')->pluck('name');

        $response = $this->patchJson(route('v1.users.update', $secondUser), [
            'permissions' => $permissions,
        ]);
        $response->assertNoContent();

        // User for other organization
        $otherUser = User::factory()->create();

        $response = $this->patchJson(route('v1.users.update', $otherUser), [
            'permissions' => $permissions,
        ]);
        $response->assertNotFound();

    });

    test('Logged user with non valid data', function () {
        $this->seed(PermissionSeeder::class);
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['user.update']);
        $response = $this->patchJson(route('v1.users.update', $user), []);
        $response->assertUnprocessable();

        $response = $this->patchJson(route('v1.users.update', $user), [
            'permissions' => ['non.existed.permission']]);

        $response->assertUnprocessable();

    });

    test('Non logged user', function () {
        $user = User::factory()->create(
        );
        $response = $this->patchJson(route('v1.users.update', $user), []);
        $response->assertUnauthorized();
    });

    test('Logged user without permission', function () {
        $user = User::factory()->create(
        );
        Sanctum::actingAs($user);
        $response = $this->patchJson(route('v1.users.update', $user), [

        ]);
        $response->assertForbidden();
    });

});
