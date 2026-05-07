<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('POST api/sellers', function () {

    test('Logged user with valid data', function () {
        Sanctum::actingAs(User::factory()->create(), ['seller.create']);

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
        Sanctum::actingAs(User::factory()->create(), ['seller.create']);

        $response = $this->postJson(route('v1.sellers.store'), []);
        $response->assertUnprocessable();
    });
    test('Logged user without permission', function () {
        Sanctum::actingAs(User::factory()->create());
        $response = $this->postJson(route('v1.sellers.store'), []);

        $response->assertNotFound();
    });
    test('Non logged user', function () {
        $response = $this->postJson(route('v1.sellers.store'), []);

        $response->assertUnauthorized();
    });
});
