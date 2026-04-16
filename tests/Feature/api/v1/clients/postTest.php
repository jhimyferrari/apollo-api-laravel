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
});
