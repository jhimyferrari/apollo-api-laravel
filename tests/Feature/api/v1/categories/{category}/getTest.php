<?php

use App\Enum\PermissionType;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
describe('GET api/category/{categories}', function () {
    test('Logged user with permission', function () {
        $user = User::factory()->create();
        $category = Category::factory()->create(['organization_id' => $user->organization_id]);

        Sanctum::actingAs($user, [PermissionType::CATEGORY_READ->value]);
        $response = $this->getJson(route('v1.categories.show', $category));

        $response->assertOk()
            ->assertJson(CategoryResource::make($category)->response()->getData(true));

        $otherOrganizationCategory = Category::factory()->create();
        $response = $this->getJson(route('v1.categories.show', $otherOrganizationCategory));
        $response->assertNotFound();
    });
    test('Logged user without permission', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $category = Category::factory()->create(['organization_id' => $user->organization_id]);
        $response = $this->getJson(route('v1.sellers.show', $category));

        $response->assertNotFound();

        $otherOrganizationCategory = Category::factory()->create();
        $response = $this->getJson(route('v1.categories.show', $otherOrganizationCategory));
        $response->assertNotFound();
    });
    test('Non logged user', function () {
        $category = Category::factory()->create();

        $response = $this->getJson(route('v1.categories.show', $category));

        $response->assertUnauthorized();
    });
});
