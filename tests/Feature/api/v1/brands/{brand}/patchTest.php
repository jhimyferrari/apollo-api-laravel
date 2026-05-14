<?php

use App\Enum\PermissionType;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
describe('PATCH api/brands/{brand}', function () {

    test('Logged user with valid data', function () {
        $user = User::factory()->create();
        $brand = Brand::factory()->create(['organization_id' => $user->organization_id]);
        Sanctum::actingAs($user, [PermissionType::BRAND_UPDATE->value]);

        $newBrandRequest = [
            'name' => fake()->name(),
            'description' => fake()->text(),
        ];
        $response = $this->patchJson(route('v1.brands.update', $brand), $newBrandRequest);

        $response->assertNoContent();
        $this->assertDatabaseHas('brands', $newBrandRequest);
    });

    test('Other organization brand', function () {
        $user = User::factory()->create();
        $brand = Brand::factory()->create();
        Sanctum::actingAs($user, [PermissionType::BRAND_UPDATE->value]);

        $response = $this->patchJson(route('v1.brands.update', $brand), []);

        $response->assertNotFound();
    });
    test('Logged user with non valid data', function () {
        $user = User::factory()->create();
        $brand = Brand::factory()->create(['organization_id' => $user->organization_id]);
        Sanctum::actingAs($user, [PermissionType::BRAND_UPDATE->value]);
        $response = $this->patchJson(route('v1.brands.update', $brand), []);

        $response->assertUnprocessable();
    });
    test('Logged user without permission', function () {
        $user = User::factory()->create();
        $brand = Brand::factory()->create(['organization_id' => $user->organization_id]);
        Sanctum::actingAs($user);
        $response = $this->patchJson(route('v1.brands.update', $brand), []);

        $response->assertNotFound();

        $otherOrganizationBrand = Brand::factory()->create();
        $response = $this->patchJson(route('v1.brands.update', $otherOrganizationBrand), []);
        $response->assertNotFound();

    });
    test('Non logged user', function () {
        $brand = Brand::factory()->create();
        $response = $this->patchJson(route('v1.brands.update', $brand), []);

        $response->assertUnauthorized();
    });
});
