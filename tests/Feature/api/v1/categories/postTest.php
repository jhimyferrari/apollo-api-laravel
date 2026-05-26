<?php

use App\Enum\PermissionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('POST api/categories', function () {

    test('Logged user with valid data', function () {
        Sanctum::actingAs(User::factory()->create(), [PermissionType::CATEGORY_CREATE->value]);

        $data = [
            'name' => fake()->word,
            'description' => fake()->text,
        ];
        $response = $this->postJson(route('v1.categories.store'), $data);
        $response->assertCreated();

    });

    test('Logged user with invalid data', function () {
        Sanctum::actingAs(User::factory()->create(), [PermissionType::CATEGORY_CREATE->value]);

        $response = $this->postJson(route('v1.categories.store'), []);
        $response->assertUnprocessable();
    });
    test('Logged user without permission', function () {
        Sanctum::actingAs(User::factory()->create());
        $response = $this->postJson(route('v1.categories.store'), []);

        $response->assertNotFound();
    });
    test('Non logged user', function () {
        $response = $this->postJson(route('v1.categories.store'), []);

        $response->assertUnauthorized();
    });
});
