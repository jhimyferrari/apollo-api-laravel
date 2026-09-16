<?php

use App\Enum\PermissionType;
use App\Models\Address;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\CitiesSeeder;
use Database\Seeders\UfSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
describe('GET api/suppliers/{supplier}/address', function () {

    test('Logged user with valid data', function () {
        $this->seed(UfSeeder::class);
        new CitiesSeeder()->run(2);

        $user = User::factory()->create();
        $supplier = Supplier::factory()->for($user->organization)->create();

        Sanctum::actingAs($user, [PermissionType::SUPPLIER_READ->value]);

        $addresses1 = Address::factory()->for($supplier, 'addressable')->for($user->organization)->count(5)->create();
        $addresses2 = Address::factory()->for($supplier, 'addressable')->for($user->organization)->count(5)->create();

        $response = $this->getJson(route('v1.suppliers.addresses.show', $supplier));

        $response->assertOk();
        $response->assertJsonCount(5, 'data');

        $data = collect($response->json('data'));
        expect($data->pluck('id'))->toContain(...$addresses1->pluck('id'));

        $response = $this->getJson(route('v1.suppliers.addresses.show', ['page' => 2, 'supplier' => $supplier]));
        $response->assertOk();
        $response->assertJsonCount(5, 'data');
        $data = collect($response->json('data'));
        expect($data->pluck('id'))->toContain(...$addresses2->pluck('id'));
    });
    test('Supplier without address', function () {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->for($user->organization)->create();
        Sanctum::actingAs($user, [PermissionType::SUPPLIER_READ->value]);

        $response = $this->getJson(route('v1.suppliers.addresses.show', $supplier));

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    });
    test('Other org supplier', function () {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create();
        Sanctum::actingAs($user, [PermissionType::SUPPLIER_READ->value]);
        $this->getJson(route('v1.suppliers.addresses.show', $supplier))
            ->assertNotFound();
    });
    test('Logged user without permission', function () {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->for($user->organization)->create();
        Sanctum::actingAs($user);
        $this->getJson(route('v1.suppliers.addresses.show', $supplier))
            ->assertNotFound();
    });
    test('Non logged user', function () {
        $supplier = Supplier::factory()->create();
        $this->getJson(route('v1.suppliers.addresses.show', $supplier))
            ->assertUnauthorized();
    });
});
