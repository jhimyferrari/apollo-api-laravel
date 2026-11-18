<?php

use App\Models\Organization;
use App\Models\Permission;
use App\Models\User;
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
            'password' => $this->validPassword(),
        ];

        $response = $this->postJson(route('v1.organizations.store'),
            $data,
        );
        $response
            ->assertCreated()
            ->assertJson(['message' => __('messages.created', ['resource' => 'Organização'])]);

        $this->assertDatabaseCount('organizations', 1);
        $this->assertDatabaseCount('users', 1);
        // With pontuation
        $data = [
            'name' => fake()->company,
            'document' => fake()->cpf(),
            'email' => fake()->email,
            'password' => $this->validPassword(),
        ];

        $response = $this->postJson(route('v1.organizations.store'), $data);
        $response->assertCreated()
            ->assertJson(['message' => __('messages.created', ['resource' => 'Organização'])]);

    });
    it('should create an Admin User with all permissions ', function () {
        $this->seed(PermissionSeeder::class);
        $data = [
            'name' => fake()->company,
            'document' => fake()->cnpj(false),
            'email' => fake()->email,
            'password' => $this->validPassword(),
        ];

        $this->postJson(route('v1.organizations.store'),
            $data,
        );

        $user = User::where('email', $data['email'])->first();
        foreach (Permission::all()->pluck('id') as $p) {
            expect($user->permissions->pluck('id'))->toContain($p);

        }

    });
    test('With unique and non valid data', function () {
        $this->assertDatabaseEmpty('organizations');
        // invalid document
        $data = [
            'name' => fake()->company,
            'document' => '11.111.111/1111-11',
            'email' => fake()->email,
            'password' => $this->validPassword(),
        ];

        $response = $this->postJson(route('v1.organizations.store'), $data);
        $response->assertUnprocessable();

        // invalid email
        $data = [
            'name' => fake()->company,
            'document' => fake()->cpf(),
            'email' => '1',
            'password' => $this->validPassword(),
        ];

        $response = $this->postJson(route('v1.organizations.store'), $data);
        $response->assertUnprocessable();

        // invalid password
        $data = [
            'name' => fake()->company,
            'document' => fake()->cpf(),
            'email' => '1',
            'password' => $this->validPassword(),
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
            'password' => $this->validPassword(),
        ];
        $response = $this->postJson(route('v1.organizations.store'), $data);
        $response->assertUnprocessable();
        $this->assertDatabaseCount('organizations', 1);
    });

    test('Fails with 422 when document is duplicated even with mask', function () {
        $this->seed(PermissionSeeder::class);
        $cnpjRaw = '11222333000181';
        $cnpjMasked = '11.222.333/0001-81';
        Organization::factory()->create(['document' => $cnpjRaw]);

        $response = $this->postJson(route('v1.organizations.store'), [
            'name' => fake()->company,
            'document' => $cnpjMasked,
            'email' => fake()->email,
            'password' => $this->validPassword(),
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('document', 'error.fields');
        $this->assertDatabaseCount('organizations', 1);
    });

    test('Fails with 422 when password does not meet complexity rules or has invalid type', function (mixed $password) {
        $this->seed(PermissionSeeder::class);

        $response = $this->postJson(route('v1.organizations.store'), [
            'name' => fake()->company,
            'document' => fake()->cnpj(false),
            'email' => fake()->email,
            'password' => $password,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('password', 'error.fields');
        $this->assertDatabaseCount('organizations', 0);
    })->with([
        'weak password' => ['abcdefgh'],
        'array password' => [array_fill(0, 8, 'a')],
    ]);

    test('Fails with 422 when document is not a string', function () {
        $this->seed(PermissionSeeder::class);

        $response = $this->postJson(route('v1.organizations.store'), [
            'name' => fake()->company,
            'document' => ['11222333000181'],
            'email' => fake()->email,
            'password' => $this->validPassword(),
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('document', 'error.fields');
        $this->assertDatabaseCount('organizations', 0);
    });

});
