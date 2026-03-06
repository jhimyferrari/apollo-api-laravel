<?php

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
describe('POST api/organizations', function () {
    describe('anonymous user', function () {
        test('With Unique and Valid data', function () {
            $this->seed(PermissionSeeder::class);

            $data = [
                'name' => 'newOrganization',
                'document' => '81185396012',
                'email' => 'uniqueEmail@email.com',
                'password' => '12345678',
            ];
            $response = $this->postJson(route('v1.organizations.store'),
                $data,
            );

            $response
                ->assertStatus(201)
                ->assertJson(['message' => 'Organization created successfully.']);

            $organization = Organization::where('document', $data['document'])->first();
            $user = User::where('organization_id', $organization['id'])->first();
        });

    });
});
