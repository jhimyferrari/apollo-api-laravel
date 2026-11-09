<?php

use App\Enum\PermissionType;
use App\Enum\Status\SellerStatus;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
describe('PATCH api/sellers/{seller}', function () {

    test('Logged user with valid data', function () {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['organization_id' => $user->organization_id]);
        Sanctum::actingAs($user, [PermissionType::SELLER_UPDATE->value]);

        $newSellerRequest = [
            'document' => fake()->cnpj(false),
            'legal_name' => fake()->domainName(),
            'trade_name' => fake()->name(),
            'state_registration' => fake()->regexify('[0-9]{9,12}'),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->email(),
        ];
        $response = $this->patchJson(route('v1.sellers.update', $seller), $newSellerRequest);

        $response->assertNoContent();
        $this->assertDatabaseHas('sellers', $newSellerRequest);
    });

    test('Logged user can update seller status', function () {
        $user = User::factory()->create();
        $seller = Seller::factory()->create([
            'organization_id' => $user->organization_id,
            'status' => SellerStatus::Active,
        ]);
        Sanctum::actingAs($user, [PermissionType::SELLER_UPDATE->value]);

        $payload = [
            'document' => fake()->cnpj(false),
            'legal_name' => fake()->company(),
            'trade_name' => fake()->company(),
            'status' => SellerStatus::Inactive->value,
        ];
        $response = $this->patchJson(route('v1.sellers.update', $seller), $payload);

        $response->assertNoContent();
        expect($seller->fresh()->status)->toBe(SellerStatus::Inactive);
    });

    test('Logged user fails to update seller with invalid status', function () {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['organization_id' => $user->organization_id]);
        Sanctum::actingAs($user, [PermissionType::SELLER_UPDATE->value]);

        $payload = [
            'document' => fake()->cnpj(false),
            'legal_name' => fake()->company(),
            'trade_name' => fake()->company(),
            'status' => 'invalid_status',
        ];
        $response = $this->patchJson(route('v1.sellers.update', $seller), $payload);

        $response->assertUnprocessable()
            ->assertJsonStructure(['error' => ['fields' => ['status']]]);
    });

    test('Other organization seller', function () {
        $user = User::factory()->create();
        $seller = Seller::factory()->create();
        Sanctum::actingAs($user, [PermissionType::SELLER_UPDATE->value]);

        $response = $this->patchJson(route('v1.sellers.update', $seller), []);

        $response->assertNotFound();
    });
    test('Logged user with non valid data', function () {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['organization_id' => $user->organization_id]);
        Sanctum::actingAs($user, [PermissionType::SELLER_UPDATE->value]);
        $response = $this->patchJson(route('v1.sellers.update', $seller), []);

        $response->assertUnprocessable();
    });
    test('Logged user without permission', function () {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['organization_id' => $user->organization_id]);
        Sanctum::actingAs($user);
        $response = $this->patchJson(route('v1.sellers.update', $seller), []);

        $response->assertForbidden();

        $otherOrganizationSeller = Seller::factory()->create();
        $response = $this->patchJson(route('v1.sellers.update', $otherOrganizationSeller), []);
        $response->assertNotFound();

    });
    test('Non logged user', function () {
        $seller = Seller::factory()->create();
        $response = $this->patchJson(route('v1.sellers.update', $seller), []);

        $response->assertUnauthorized();
    });
});
