<?php

use App\Models\Organization;
use App\Models\Permission;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;

uses(RefreshDatabase::class);
describe('Create Organization and Login flow', function () {
    test('With valid data', function () {
        $this->seed(PermissionSeeder::class);
        // Creating a new organization
        $data = [
            'name' => 'OrganizationFlow',
            'document' => '30053961000125',
            'email' => 'emailFlow@email.com',
            'password' => '12345678',
        ];
        $response = $this->postJson(route('v1.organizations.store'),
            $data,
        );

        $response->assertStatus(201);
        $responseBody = $response->json();

        expect($responseBody['message'])->toBe('Organization created successfully.');
        $createdOrganization = Organization::where('document', $data['document'])->exists();
        expect($createdOrganization)->toBeTrue();

        // Doing Login using the Admin User
        $data = [
            'email' => 'emailFlow@email.com',
            'password' => '12345678',
            'organization_document' => $data['document'],
        ];
        $response = $this->postJson(route('v1.login'),
            $data,
        );
        $response->assertStatus(200);
        $token = $response->json()['data']['token'];

        $accessToken = PersonalAccessToken::findToken($token);
        expect($accessToken)->not->toBeNull();
        $permissionsAdminMustHave = Permission::all()->pluck('name')->toArray();
        expect($accessToken['abilities'])->toEqualCanonicalizing($permissionsAdminMustHave);
    });
});
