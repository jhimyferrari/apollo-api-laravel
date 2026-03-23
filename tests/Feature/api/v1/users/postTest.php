<?php

use App\Models\Permission;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
describe('POST api/users', function () {
    test('Logged user with valid data', function () {
        $this->seed(PermissionSeeder::class);

        $user = User::factory()->create();
        $this->assertDatabaseCount('users', 1);

        Sanctum::actingAs($user, ['user.create']);
        // creating a user without permissions
        $data = [
            'name' => fake()->name,
            'email' => fake()->email,
            'password' => fake()->password(8),
            'organization_id' => $user['organization_id'],
        ];
        $response = $this->postJson(
            route('v1.users.store'),
            $data
        );
        $this->assertDatabaseCount('users', 2);
        $response
            ->assertStatus(201)
            ->assertJson(['message' => 'User created successfully.']);

        // creating a user with permission
        $permissions = Permission::where('name', 'like', 'user.%')->pluck('name');
        $data = [
            'name' => fake()->name,
            'email' => fake()->email,
            'password' => fake()->password(8),
            'organization_id' => $user['organization_id'],
            'permissions' => $permissions,
        ];
        $response = $this->postJson(
            route('v1.users.store'),
            $data
        );
        $response
            ->assertStatus(201)
            ->assertJson(['message' => 'User created successfully.']);

        $user = User::where('email', $data['email'])->first();
        // assert user has all permissions passed by post
        $this->assertTrue(
            $user->permissions()->pluck('name')->diff($permissions)->isEmpty());

    });
    test('Non-logged user', function () {

        $response = $this->postJson(route('v1.users.store'),
            [],
        );
        $this->assertDatabaseEmpty('users');
        $response->assertStatus(401);
    });
    test('Logged user with non valid data', function () {
        $this->seed(PermissionSeeder::class);
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['user.create']);
        $data = [
            'name' => fake()->name,
            'email' => fake()->email,
            'password' => fake()->password(minLength: 8),
            'organization_id' => '9',
        ];
        $reponse = $this->postJson(
            route('v1.users.store'),
            $data
        );
        $reponse->assertStatus(422);
    });
    test('Logged user without permission', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $response = $this->postJson(route('v1.users.store'));
        $response->assertStatus(403);
    });
});
