<?php

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
describe('POST api/organizations', function () {
    test('With Unique and Valid data', function () {
        $this->assertDatabaseEmpty('organizations');
        $this->assertDatabaseEmpty('users');

        $this->seed(PermissionSeeder::class);
        $data = [
            'name' => 'newOrganization',
            'document' => '81185396012',
            'email' => 'uniqueEmail@email.com',
            'password' => '12345678',
        ];
        echo fakePassword(8, 20);

        $response = $this->postJson(route('v1.organizations.store'),
            $data,
        );
        $this->assertDatabaseCount('organizations', 1);
        $this->assertDatabaseCount('users', 1);
        $response
            ->assertStatus(201)
            ->assertJson(['message' => 'Organization created successfully.']);

    });

});
