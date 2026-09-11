<?php

use App\Enum\PermissionType;
use App\Models\Address;
use App\Models\Seller;
use App\Models\User;
use Database\Seeders\CitiesSeeder;
use Database\Seeders\UfSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
describe('GET api/sellers/{seller}/address', function () {
    test('Logged user with valid data', function () {
        $this->seed(UfSeeder::class);
        new CitiesSeeder()->run(2);

        $user = User::factory()->create();
        $seller = Seller::factory()->for($user->organization)->create();

        Sanctum::actingAs($user, [PermissionType::SELLER_READ->value]);

        $addresses1 = Address::factory()->for($seller, 'addressable')->for($user->organization)->count(5)->create();
        $addresses2 = Address::factory()->for($seller, 'addressable')->for($user->organization)->count(5)->create();

        $response = $this->getJson(route('v1.sellers.addresses.show', $seller));

        $response->assertOk();
        $response->assertJsonCount(5, 'data');

        $data = collect($response->json('data'));
        expect($data->pluck('id'))->toContain(...$addresses1->pluck('id'));

        $response = $this->getJson(route('v1.sellers.addresses.show', ['page' => 2, 'seller' => $seller]));
        $response->assertOk();
        $response->assertJsonCount(5, 'data');
        $data = collect($response->json('data'));
        expect($data->pluck('id'))->toContain(...$addresses2->pluck('id'));
    });
    test('Seller without address', function () {
        $user = User::factory()->create();
        $seller = Seller::factory()->for($user->organization)->create();
        Sanctum::actingAs($user, [PermissionType::SELLER_READ->value]);

        $response = $this->getJson(route('v1.sellers.addresses.show', $seller));

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    });
    test('Other org seller', function () {
        $user = User::factory()->create();
        $seller = Seller::factory()->create();
        Sanctum::actingAs($user, [PermissionType::SELLER_READ->value]);
        $this->getJson(route('v1.sellers.addresses.show', $seller))
            ->assertNotFound();
    });
    test('Logged user without permission', function () {
        $user = User::factory()->create();
        $seller = Seller::factory()->for($user->organization)->create();
        Sanctum::actingAs($user);
        $this->getJson(route('v1.sellers.addresses.show', $seller))
            ->assertNotFound();
    });
    test('Non logged user', function () {
        $seller = Seller::factory()->create();
        $this->getJson(route('v1.sellers.addresses.show', $seller))
            ->assertUnauthorized();
    });
});
