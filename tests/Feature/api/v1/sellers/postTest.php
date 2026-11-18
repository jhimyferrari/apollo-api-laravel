<?php

use App\Enum\PermissionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('POST api/sellers', function () {

    test('Logged user with valid data', function () {
        Sanctum::actingAs(User::factory()->create(), [PermissionType::SELLER_CREATE->value]);

        $data = [
            'document' => fake()->cnpj(),
            'legal_name' => fake()->name,
            'trade_name' => fake()->name,
            'state_registration' => fake()->regexify('[0-9]{9,12}'),
            'email' => fake()->email,
            'phone' => fake()->phoneNumber,
        ];
        $response = $this->postJson(route('v1.sellers.store'), $data);
        $response->assertCreated();

    });

    test('Logged user with invalid data', function () {
        Sanctum::actingAs(User::factory()->create(), [PermissionType::SELLER_CREATE->value]);

        $response = $this->postJson(route('v1.sellers.store'), []);
        $response->assertUnprocessable();
    });
    test('Logged user without permission', function () {
        Sanctum::actingAs(User::factory()->create());
        $response = $this->postJson(route('v1.sellers.store'), []);

        $response->assertForbidden();
    });
    test('Non logged user', function () {
        $response = $this->postJson(route('v1.sellers.store'), []);

        $response->assertUnauthorized();
    });

    test('Logged user fails with 422 when ended_at is in the past without started_at', function () {
        Sanctum::actingAs(User::factory()->create(), [PermissionType::SELLER_CREATE->value]);

        $response = $this->postJson(route('v1.sellers.store'), [
            'document' => fake()->cnpj(false),
            'legal_name' => fake()->company(),
            'trade_name' => fake()->name(),
            'ended_at' => now()->subDay()->toDateString(),
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('ended_at', 'error.fields');
    });

    test('Logged user fails with 422 for invalid document type, punctuation-only state_registration, or invalid phone', function (array $override, string $errorField) {
        Sanctum::actingAs(User::factory()->create(), [PermissionType::SELLER_CREATE->value]);

        $payload = array_merge([
            'document' => fake()->cnpj(false),
            'legal_name' => fake()->company(),
            'trade_name' => fake()->name(),
        ], $override);

        $response = $this->postJson(route('v1.sellers.store'), $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors($errorField, 'error.fields');
    })->with([
        'non-string document' => [['document' => ['12345678000195']], 'document'],
        'punctuation only state_registration' => [['state_registration' => '---'], 'state_registration'],
        'invalid phone' => [['phone' => 'abcdef'], 'phone'],
    ]);
});
