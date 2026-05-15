<?php

use App\Enum\PermissionType;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
describe('PATCH api/categories/{category}', function () {

    test('Logged user with valid data', function () {
        $user = User::factory()->create();
        $category = Category::factory()->create(['organization_id' => $user->organization_id]);
        Sanctum::actingAs($user, [PermissionType::CATEGORY_UPDATE->value]);

        $newCategoryRequest = [
            'name' => fake()->name,
            'description' => fake()->text(),
        ];
        $response = $this->patchJson(route('v1.categories.update', $category), $newCategoryRequest);

        $response->assertNoContent();
        $this->assertDatabaseHas('categories', $newCategoryRequest);
    });

    test('Other organization category', function () {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        Sanctum::actingAs($user, [PermissionType::CATEGORY_UPDATE->value]);

        $response = $this->patchJson(route('v1.categories.update', $category), []);

        $response->assertNotFound();
    });
    test('Logged user with non valid data', function () {
        $user = User::factory()->create();
        $category = Category::factory()->create(['organization_id' => $user->organization_id]);
        Sanctum::actingAs($user, [PermissionType::CATEGORY_UPDATE->value]);
        $response = $this->patchJson(route('v1.categories.update', $category), []);

        $response->assertUnprocessable();
    });
    test('Logged user without permission', function () {
        $user = User::factory()->create();
        $category = Category::factory()->create(['organization_id' => $user->organization_id]);
        Sanctum::actingAs($user);
        $response = $this->patchJson(route('v1.categories.update', $category), []);

        $response->assertNotFound();

        $otherOrganizationCategory = Category::factory()->create();
        $response = $this->patchJson(route('v1.categories.update', $otherOrganizationCategory), []);
        $response->assertNotFound();

    });
    test('Non logged user', function () {
        $category = Category::factory()->create();
        $response = $this->patchJson(route('v1.categories.update', $category), []);

        $response->assertUnauthorized();
    });
});
