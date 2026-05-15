<?php

use App\Enum\PermissionType;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
describe('DELETE api/categories/{category}', function () {

    test('Logged user with permission', function () {
        $user = User::factory()->create();
        $category = Category::factory()->create(['organization_id' => $user->organization_id]);

        Sanctum::actingAs($user, [PermissionType::CATEGORY_DELETE->value]);
        $response = $this->deleteJson(route('v1.categories.destroy', $category));

        $response->assertNoContent();
        $this->assertSoftDeleted($category);

        $otherOrganizationCategory = Category::factory()->create();

        $response = $this->deleteJson(route('v1.categories.destroy', $otherOrganizationCategory));

        $response->assertNotFound();
    });
    test('Logged user without permission', function () {
        $user = User::factory()->create();
        $category = Category::factory()->create(['organization_id' => $user->organization_id]);

        Sanctum::actingAs($user);
        $response = $this->deleteJson(route('v1.categories.destroy', $category));

        $response->assertNotFound();

        $otherOrganizationCategory = Category::factory()->create();

        $response = $this->deleteJson(route('v1.categories.destroy', $otherOrganizationCategory));

        $response->assertNotFound();
    });
    test('Non logged user', function () {
        $category = Category::factory()->create();
        $response = $this->deleteJson(route('v1.categories.destroy', $category));

        $response->assertUnauthorized();
    });
});
