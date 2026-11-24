<?php

use App\Models\Organization;
use App\Models\Permission;
use App\Models\User;
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
        $response->assertStatus(200)
            ->assertJsonPath('data.user.is_administrator', true)
            ->assertJsonMissingPath('data.user.organization_id')
            ->assertJsonMissingPath('data.user.deleted_at');
        $token = $response->json()['data']['token'];
        expect($response->json('data.expires_at'))->not->toBeEmpty();

        $accessToken = PersonalAccessToken::findToken($token);
        expect($accessToken)->not->toBeNull()
            ->and($accessToken->expires_at)->not->toBeNull();
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

    test('Login matches email case-insensitively', function () {
        $this->seed(PermissionSeeder::class);
        $cnpj = fake()->cnpj(false);
        $password = $this->validPassword();

        $this->postJson(route('v1.organizations.store'), [
            'name' => fake()->company(),
            'document' => $cnpj,
            'email' => 'admin@empresa.com',
            'password' => $password,
        ])->assertCreated();

        $this->postJson(route('v1.login'), [
            'email' => 'Admin@EMPRESA.COM',
            'password' => $password,
            'organization_document' => $cnpj,
        ])->assertOk()
            ->assertJsonPath('data.user.email', 'admin@empresa.com');
    });

    test('Login returns standardized 401 for nonexistent organization, nonexistent user, or wrong password', function () {
        $organization = Organization::factory()->create([
            'document' => '11222333000181',
        ]);
        User::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'user@apollo.test',
            'password' => $this->validPassword(),
        ]);

        // Organização inexistente
        $this->postJson(route('v1.login'), [
            'email' => 'user@apollo.test',
            'password' => $this->validPassword(),
            'organization_document' => fake()->cnpj(false),
        ])->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');

        // E-mail inexistente na organização
        $this->postJson(route('v1.login'), [
            'email' => 'other@apollo.test',
            'password' => $this->validPassword(),
            'organization_document' => '11222333000181',
        ])->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');

        // Senha incorreta
        $this->postJson(route('v1.login'), [
            'email' => 'user@apollo.test',
            'password' => 'WrongPass123!',
            'organization_document' => '11222333000181',
        ])->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    });

    test('Login returns 429 TOO_MANY_REQUESTS when rate limit is exceeded', function () {
        $payload = [
            'email' => 'ratelimit@apollo.test',
            'password' => 'WrongPass123!',
            'organization_document' => '11222333000181',
        ];

        for ($i = 0; $i < 10; $i++) {
            $this->postJson(route('v1.login'), $payload)->assertUnauthorized();
        }

        $this->postJson(route('v1.login'), $payload)
            ->assertTooManyRequests()
            ->assertJsonPath('error.code', 'TOO_MANY_REQUESTS');
    });
});
