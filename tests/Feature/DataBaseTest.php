<?php

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
describe('Integrit of the database', function () {
    describe('Models should work', function () {
        test('Organization Model', function () {
            $organization = Organization::factory()->create();
            $this->assertDatabaseCount('organizations', 1);
            $this->assertDatabaseHas(
                'organizations',
                ['document' => $organization['document']]
            );
        });
        test('User Model', function () {
            $this->assertDatabaseCount('users', 0);
            $user = User::factory()->create();
            $this->assertDatabaseCount('users', 1);
            $this->assertDatabaseHas(
                'users',
                ['email' => $user['email']]
            );
        });
    });
});
