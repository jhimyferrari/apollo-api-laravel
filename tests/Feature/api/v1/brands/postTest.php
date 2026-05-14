<?php

use App\Enum\PermissionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
describe('POST api/brands', function () {
    test('Logged user with valid data', function () {
        Sanctum::actingAs(User::factory()->create(), [PermissionType::BRAND_CREATE->value]);
        $data = [
            'name' => fake()->name,
            'description' => fake()->text,
        ];
        $response = $this->postJson(route('v1.brands.store'), $data);
        $response->assertCreated();
        expect($response->json('data')['number'])->toBe(1);
        $this->assertDatabaseHas('brands', $data);

    });
    test('Logged user with invalid data', function () {
        Sanctum::actingAs(User::factory()->create(), [PermissionType::BRAND_CREATE->value]);
        $response = $this->postJson(route('v1.brands.store'), []);
        $response->assertUnprocessable();
    });
    test('Logged user without permission', function () {
        Sanctum::actingAs(User::factory()->create());
        $response = $this->postJson(route('v1.brands.store'), []);
        $response->assertNotFound();
    });
    test('Non logged user', function () {
        $response = $this->postJson(route('v1.brands.store'), []);
        $response->assertUnauthorized();
    });
});
