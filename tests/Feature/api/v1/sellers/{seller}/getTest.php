<?php

use App\Enum\PermissionType;
use App\Http\Resources\SellerResource;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
describe('GET api/sellers/{seller}', function () {
    test('Logged user with permission', function () {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['organization_id' => $user->organization_id]);

        Sanctum::actingAs($user, [PermissionType::SELLER_READ->value]);
        $response = $this->getJson(route('v1.sellers.show', $seller));

        $response->assertOk()
            ->assertJson(SellerResource::make($seller)->response()->getData(true));

        $otherOrganizationSeller = Seller::factory()->create();
        $response = $this->getJson(route('v1.sellers.show', $otherOrganizationSeller));
        $response->assertNotFound();
    });
    test('Logged user without permission', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $seller = Seller::factory()->create(['organization_id' => $user->organization_id]);
        $response = $this->getJson(route('v1.sellers.show', $seller));

        $response->assertNotFound();

        $otherOrganizationSeller = Seller::factory()->create();
        $response = $this->getJson(route('v1.sellers.show', $otherOrganizationSeller));
        $response->assertNotFound();
    });
    test('Non logged user', function () {
        $seller = Seller::factory()->create();

        $response = $this->getJson(route('v1.sellers.show', $seller));

        $response->assertUnauthorized();
    });
});
