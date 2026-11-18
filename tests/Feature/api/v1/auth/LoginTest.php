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
            'name' => fake()->domainName,
            'document' => fake()->cpf(false),
            'email' => fake()->email,
            'password' => $this->validPassword(),
        ];
        $response = $this->postJson(route('v1.organizations.store'),
            $data,
        );

        $response->assertStatus(201);
        $responseBody = $response->json();

        expect($responseBody['message'])->toBe(__('messages.created', ['resource' => 'Organização']));
        $this->assertDatabaseHas('organizations', ['document' => $data['document']]);

        // Doing Login using the Admin User
        $data = [
            'email' => $data['email'],
            'password' => $data['password'],
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

    test('Login succeeds when organization_document is sent with mask', function () {
        $this->seed(PermissionSeeder::class);
        $cnpjMasked = fake()->cnpj(true);
        $email = fake()->email();
        $password = $this->validPassword();

        $this->postJson(route('v1.organizations.store'), [
            'name' => fake()->company(),
            'document' => $cnpjMasked,
            'email' => $email,
            'password' => $password,
        ])->assertCreated();

        $response = $this->postJson(route('v1.login'), [
            'email' => $email,
            'password' => $password,
            'organization_document' => $cnpjMasked,
        ]);

        $response->assertOk();
        expect($response->json('data.token'))->not->toBeEmpty();
    });

    test('Login fails with 422 when organization_document or password has invalid format or type', function (array $payload, string $errorField) {
        $response = $this->postJson(route('v1.login'), $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors($errorField, 'error.fields');
    })->with([
        'invalid document' => [
            ['email' => 'admin@test.com', 'password' => 'Str0ng!Pass', 'organization_document' => '11.111.111/1111-11'],
            'organization_document',
        ],
        'array document' => [
            ['email' => 'admin@test.com', 'password' => 'Str0ng!Pass', 'organization_document' => ['123']],
            'organization_document',
        ],
        'array password' => [
            ['email' => 'admin@test.com', 'password' => array_fill(0, 8, 'a'), 'organization_document' => '11222333000181'],
            'password',
        ],
    ]);
});
