
<?php

use App\Enum\PermissionType;
use App\Models\Brand;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
describe('GET api/brands', function () {
    test('Logged user with valid data', function () {
        $user = User::factory()->create();

        $otherOrganization = Organization::factory()->create();
        Brand::factory()->count(5)->create(
            ['organization_id' => $otherOrganization->id]
        );

        Brand::factory()->count(20)->create(
            ['organization_id' => $user->organization_id]
        );
        Sanctum::actingAs($user, [PermissionType::BRAND_READ->value]);

        $response = $this->getJson(route('v1.brands.index'));

        $response->assertOk();
        $response->assertJsonCount(15, 'data');

        $firstRequestData = collect($response->json('data'));
        $ids = $firstRequestData->pluck('organization_id');
        $this->assertNotContains($otherOrganization->id, $ids);

        $response = $this->getJson(route('v1.brands.index', ['page' => 2]));
        $response
            ->assertOk()
            ->assertJsonCount(5, 'data');
    });
    test('Non logged user', function () {
        $response = $this->getJson(route('v1.brands.index'));
        $response->assertUnauthorized();
    });

    test('Logged user without permission', function () {

        Sanctum::actingAs(User::factory()->create());
        $response = $this->getJson(route('v1.brands.index'));
        $response->assertNotFound();
    });
});
