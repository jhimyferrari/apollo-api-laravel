<?php

use App\Enum\PermissionType;
use App\Models\Organization;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
describe('GET api/suppliers', function () {
    test('Logged user with permission', function () {
        $user = User::factory()->create();

        $otherOrganization = Organization::factory()->create();
        Supplier::factory()->count(20)->create(['organization_id' => $user->organization_id]);

        Supplier::factory()->count(7)->create(['organization_id' => $otherOrganization->id]);

        Sanctum::actingAs($user, [PermissionType::SUPPLIER_READ->value]);
        $response = $this->getJson(route('v1.suppliers.index'));

        $response->assertOk();
        $response->assertJsonCount(15, 'data');

        $firstRequestData = collect($response->json('data'));
        $ids = $firstRequestData->pluck('organization_id');
        $this->assertNotContains($otherOrganization, $ids);

        $response = $this->getJson(route('v1.suppliers.index', ['page' => 2]));
        $response
            ->assertOk()
            ->assertJsonCount(5, 'data');

    });
    test('Logged user without permission', function () {
        Sanctum::actingAs(User::factory()->create());
        $response = $this->getJson(route('v1.suppliers.index'));

        $response->assertNotFound();

    });
    test('Non logged user', function () {
        $response = $this->getJson(route('v1.suppliers.index'));

        $response->assertUnauthorized();

    });
});
