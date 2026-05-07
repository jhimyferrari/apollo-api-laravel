<?php

use App\Models\Seller;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
describe('DELETE api/sellers/{seller}', function () {

    test('Logged user with permission', function () {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['organization_id' => $user->organization_id]);

        Sanctum::actingAs($user, ['seller.delete']);
        $response = $this->deleteJson(route('v1.sellers.destroy', $seller));

        $response->assertNoContent();
        $this->assertSoftDeleted($seller);

        $otherOrganizationSeller = Seller::factory()->create();

        $response = $this->deleteJson(route('v1.sellers.destroy', $otherOrganizationSeller));

        $response->assertNotFound();
    });
    test('Logged user without permission', function () {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['organization_id' => $user->organization_id]);

        Sanctum::actingAs($user);
        $response = $this->deleteJson(route('v1.sellers.destroy', $seller));

        $response->assertNotFound();

        $otherOrganizationSeller = Seller::factory()->create();

        $response = $this->deleteJson(route('v1.sellers.destroy', $otherOrganizationSeller));

        $response->assertNotFound();
    });
    test('Non logged user', function () {
        $seller = Seller::factory()->create();
        $response = $this->deleteJson(route('v1.sellers.destroy', $seller));

        $response->assertUnauthorized();
    });
});
