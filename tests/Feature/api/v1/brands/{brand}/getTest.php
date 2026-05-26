<?php

use App\Enum\PermissionType;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
describe('GET api/brands/{brand}', function () {
    test('Logged user with permission', function () {
        $user = User::factory()->create();
        $brand = Brand::factory()->create([
            'organization_id' => $user->organization_id,
        ]);

        Sanctum::actingAs($user, [PermissionType::BRAND_READ->value]);

        $response = $this->getJson(route('v1.brands.show', $brand));

        $response->assertOk()
            ->assertJson(BrandResource::make($brand)->response()->getData(true));

        $otherBrand = Brand::factory()->create();
        $response = $this->getJson(route('v1.brands.show', $otherBrand));

        $response->assertNotFound();

    });
    test('Non logged user', function () {
        $brand = Brand::factory()->create();
        $response = $this->getJson(route('v1.brands.show', $brand));

        $response->assertUnauthorized();
    });
    test('Logged user without permission', function () {
        $user = User::factory()->create();
        $brand = Brand::factory()->create([
        ]);
        Sanctum::actingAs($user);
        $response = $this->getJson(route('v1.brands.show', $brand));

        $response->assertNotFound();

        $brand = Brand::factory()->create(['organization_id' => $user->organization_id]);
        $response = $this->getJson(route('v1.brands.show', $brand));

        $response->assertNotFound();

    });
});
