<?php

use App\Enum\ErrorType;
use App\Enum\PermissionType;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\User;
use App\ValueObjects\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertSoftDeleted;

uses(RefreshDatabase::class);
describe('DELETE api/products/{product}', function () {
    test('Logged user with permission', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user, [PermissionType::PRODUCT_DELETE->value]);
        $product = Product::factory()->create(['organization_id' => $user->organization_id]);
        $response = $this->deleteJson(route('v1.products.destroy', $product));

        $response->assertNoContent();
        assertSoftDeleted($product);
    });
    test('Other Organization product', function () {
        Sanctum::actingAs(User::factory()->create(), [PermissionType::PRODUCT_DELETE->value]);
        $product = Product::factory()->create();
        $response = $this->deleteJson(route('v1.products.destroy', $product));

        $response->assertNotFound();

    });
    test('Logged user without permission', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $product = Product::factory()->create(['organization_id' => $user->organization_id]);
        $response = $this->deleteJson(route('v1.products.destroy', $product));
        $response->assertForbidden();

    });
    test('Non logged user', function () {
        $product = Product::factory()->create();
        $response = $this->deleteJson(route('v1.products.destroy', $product));
        $response->assertUnauthorized();
    });

    test('Logged user cannot delete product with dependent sales', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user, [PermissionType::PRODUCT_DELETE->value]);
        $product = Product::factory()->create(['organization_id' => $user->organization_id]);
        $order = SalesOrder::factory()->create([
            'organization_id' => $user->organization_id,
        ]);
        $order->items()->create([
            'organization_id' => $user->organization_id,
            'product_id' => $product->id,
            'number' => 1,
            'quantity' => 1,
            'unit_price' => Money::fromDecimal('10.0000'),
            'subtotal' => Money::fromDecimal('10.0000'),
        ]);

        $response = $this->deleteJson(route('v1.products.destroy', $product));

        $response->assertStatus(422)
            ->assertJson([
                'error' => [
                    'code' => ErrorType::ResourceHasDependencies->value,
                ],
            ]);
    });

});
