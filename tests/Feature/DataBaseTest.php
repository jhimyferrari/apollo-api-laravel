<?php

use App\Exceptions\Auth\ForbiddenException;
use App\Models\Address;
use App\Models\Client;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\CitiesSeeder;
use Database\Seeders\UfSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
describe('Integrit of the database', function () {
    describe('Models should work', function () {
        test('Organization Model', function () {
            $this->assertDatabaseEmpty('organizations');
            $organization = Organization::factory()->create();
            $this->assertDatabaseCount('organizations', 1);
            $this->assertDatabaseHas(
                'organizations',
                ['document' => $organization['document']]
            );
        });
        test('User Model', function () {
            $this->assertDatabaseEmpty('users');
            $user = User::factory()->create();
            $this->assertDatabaseCount('users', 1);
            $this->assertDatabaseHas(
                'users',
                ['email' => $user['email']]
            );

            $user->getRawOriginal('password');

        });
        test('Address Model', function () {
            $this->seed(UfSeeder::class);
            $seed = new CitiesSeeder;
            $seed->run(50);
            $this->assertDatabaseEmpty('addresses');
            $address = Address::factory()->create();
            $this->assertDatabaseHas(
                'addresses',
                ['id' => $address['id']]
            );
        });
        test('Clients Model', function () {
            $this->assertDatabaseEmpty('clients');
            $client = Client::factory()->create();
            $this->assertDatabaseCount('clients', 1);
            $this->assertDatabaseHas(
                'clients',
                ['document' => $client['document']]
            );
        });
    });
    describe('Models should have security', function () {
        describe('users', function () {
            it('throws exception for try to change user organization ', function () {
                $user = User::factory()->create();

                $otherOrganization = Organization::factory()->create();

                $user->organization_id = $otherOrganization->id;

                $user->save();

            })->throws(ForbiddenException::class);
        });
        describe('clients', function () {
            it('throw exception for try to change client organization', function () {
                $client = Client::factory()->create();

                $otherOrganization = Organization::factory()->create();

                $client->organization_id = $otherOrganization->id;

                $client->save();
            })->throws(ForbiddenException::class);
        });
    });

});
