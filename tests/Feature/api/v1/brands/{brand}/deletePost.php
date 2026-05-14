<?php

use App\Enum\PermissionType;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
describe('DELETE api/brands/{brand}', function () {
    test('Logged user with permission', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user, [PermissionType::BRAND_DELETE->value]);
        $brand = Brand::factory()->create(
            ['organization_id' => $user->organization_id]);
        $response = $this->deleteJson(route('v1.brands.destroy', $brand));
        $response->assertNoContent();
        $this->assertSoftDeleted($brand);
    });
    test('Logged user without permission', function () {
        $user = User::factory()->create();
        $brand = Brand::factory()->create();
        Sanctum::actingAs($user);
        $response = $this->deleteJson(route('v1.brands.destroy', $brand));
        $response->assertNotFound();

        $brand = Brand::factory()->create(
            ['organization_id' => $user->organization_id]
        );

        $response = $this->deleteJson(route('v1.brands.destroy', $brand));
        $response->assertNotFound();

    });
    test('Non logged user', function () {
        $brand = Brand::factory()->create();
        $response = $this->deleteJson(route('v1.brands.destroy', $brand));
        $response->assertUnauthorized();
    });
});
