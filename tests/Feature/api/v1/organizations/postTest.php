<?php

use App\Models\Organization;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('POST api/organizations', function () {
    test('With Unique and Valid data', function () {
        $this->assertDatabaseEmpty('organizations');
        $this->assertDatabaseEmpty('users');

        $this->seed(PermissionSeeder::class);
        // Without pontuation
        $data = [
            'name' => fake()->company,
            'document' => fake()->cnpj(false),
            'email' => fake()->email,
            'password' => fake()->password(8),
        ];

        $response = $this->postJson(route('v1.organizations.store'),
            $data,
        );
        $response
            ->assertCreated()
            ->assertJson(['message' => 'Organization created successfully.']);

        $this->assertDatabaseCount('organizations', 1);
        $this->assertDatabaseCount('users', 1);
        // With pontuation
        $data = [
            'name' => fake()->company,
            'document' => fake()->cpf(),
            'email' => fake()->email,
            'password' => fake()->password(8),
        ];

        $response = $this->postJson(route('v1.organizations.store'), $data);
        $response->assertCreated()
            ->assertJson(['message' => 'Organization created successfully.']);

    });
    test('With unique and non valid data', function () {
        $this->assertDatabaseEmpty('organizations');
        // invalid document
        $data = [
            'name' => fake()->company,
            'document' => '11.111.111/1111-11',
            'email' => fake()->email,
            'password' => fake()->password(8),
        ];

        $response = $this->postJson(route('v1.organizations.store'), $data);
        $response->assertUnprocessable();

        // invalid email
        $data = [
            'name' => fake()->company,
            'document' => fake()->cpf(),
            'email' => '1',
            'password' => fake()->password(8),
        ];

        $response = $this->postJson(route('v1.organizations.store'), $data);
        $response->assertUnprocessable();

        // invalid password
        $data = [
            'name' => fake()->company,
            'document' => fake()->cpf(),
            'email' => '1',
            'password' => fake()->password(7, 7),
        ];

        $response = $this->postJson(route('v1.organizations.store'), $data);
        $response->assertUnprocessable();
    });
    test('With valid data and non unique', function () {
        $this->seed(PermissionSeeder::class);
        $organization = Organization::factory()->create();
        $this->assertDatabaseCount('organizations', 1);
        $data = [
            'name' => fake()->company,
            'document' => $organization->id,
            'email' => fake()->email,
            'password' => fake()->password(8),
        ];
        $response = $this->postJson(route('v1.organizations.store'), $data);
        $response->assertUnprocessable();
        $this->assertDatabaseCount('organizations', 1);
    });

});
