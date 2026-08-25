<?php

use App\Enum\PermissionType;
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

        Sanctum::actingAs($user, [PermissionType::USER_CREATE->value]);

        // creating a user without permissions
        $passwordPlain = $this->validPassword();
        $data = [
            'name' => fake()->name,
            'email' => fake()->email,
            'password' => $passwordPlain,
        ];
        $response = $this->postJson(
            route('v1.users.store'),
            $data
        );
        $this->assertDatabaseCount('users', 2);
        $response
            ->assertStatus(201)
            ->assertJson(['message' => 'User created successfully.']);
        $userWithoutPermission = User::where('email', $data['email'])->first();

        // assert password is hashed
        $this->assertTrue(Hash::check($passwordPlain, $userWithoutPermission->getRawOriginal('password')));

        // creating a user with permission
        $permissions = Permission::where('name', 'like', 'user.%')->pluck('name');
        $data = [
            'name' => fake()->name,
            'email' => fake()->email,
            'password' => $this->validPassword(),
            'permissions' => $permissions,
        ];
        $response = $this->postJson(
            route('v1.users.store'),
            $data
        );
        $response
            ->assertStatus(201)
            ->assertJson(['message' => 'User created successfully.']);

        // assert user has all permissions passed by post
        $userWithPermissions = User::where('email', $data['email'])->first();
        $this->assertTrue(
            $userWithPermissions->permissions()->pluck('name')->diff($permissions)->isEmpty());

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

        Sanctum::actingAs($user, [PermissionType::USER_CREATE->value]);
        $data = [
            'name' => fake()->name,
            'email' => 'wrongEmail@',
            'password' => $this->validPassword(),
        ];
        $reponse = $this->postJson(
            route('v1.users.store'),
            $data
        );
        $reponse->assertStatus(422);
    });
    test('Logged user without permission', function () {
        Sanctum::actingAs(User::factory()->create());
        $response = $this->postJson(route('v1.users.store'));
        $response->assertNotFound();
    });
});
