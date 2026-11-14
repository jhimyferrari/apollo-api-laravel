<?php

use App\Enum\ErrorType;
use App\Enum\PermissionType;
use App\Models\Category;
use App\Models\Product;
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

        $response->assertForbidden();

        $otherOrganizationCategory = Category::factory()->create();

        $response = $this->deleteJson(route('v1.categories.destroy', $otherOrganizationCategory));

        $response->assertNotFound();
    });
    test('Non logged user', function () {
        $category = Category::factory()->create();
        $response = $this->deleteJson(route('v1.categories.destroy', $category));

        $response->assertUnauthorized();
    });

    test('Logged user cannot delete category with products', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user, [PermissionType::CATEGORY_DELETE->value]);
        $category = Category::factory()->create(['organization_id' => $user->organization_id]);
        $product = Product::factory()->create(['organization_id' => $user->organization_id]);
        $product->categories()->sync([$category->id]);

        $response = $this->deleteJson(route('v1.categories.destroy', $category));

        $response->assertStatus(422)
            ->assertJson([
                'error' => [
                    'code' => ErrorType::ResourceHasDependencies->value,
                ],
            ]);
    });
});
