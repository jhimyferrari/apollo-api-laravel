<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
describe('POST api/clients', function () {
    test('Logged with valid data', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['client.create']);
        $data = [
            'document' => fake()->cnpj(),
            'legal_name' => fake()->name,
            'trade_name' => fake()->name,
            'state_registration' => fake()->regexify('[0-9]{9,12}'),
            'email' => fake()->email,
            'phone' => fake()->phoneNumber,
        ];

        $response = $this->postJson(route('v1.clients.store'), $data);

        $response->assertCreated();
        expect($response->json('data')['number'])->toBe(1);

    });
    test('Non logged user', function () {
        $response = $this->postJson(route('v1.clients.store'));

        $response->assertUnauthorized();
    });

    test('Logged user without permission', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $response = $this->postJson(route('v1.clients.store'));

        $response->assertForbidden();
    });

    test('Logged user fails with 422 for non-string document, punctuation-only state_registration, or invalid phone', function (array $override, string $errorField) {
        Sanctum::actingAs(User::factory()->create(), ['client.create']);

        $payload = array_merge([
            'document' => fake()->cnpj(false),
            'legal_name' => fake()->company(),
            'trade_name' => fake()->name(),
        ], $override);

        $response = $this->postJson(route('v1.clients.store'), $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors($errorField, 'error.fields');
    })->with([
        'array document' => [['document' => ['123']], 'document'],
        'punctuation only state_registration' => [['state_registration' => '---'], 'state_registration'],
        'invalid phone' => [['phone' => 'abcdef'], 'phone'],
    ]);
});
