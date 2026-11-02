<?php

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function draftOrderFor(User $user, Model $model): SalesOrder|PurchaseOrder
{
    $organization = $user->organization;
    $product = Product::factory()->for($organization)->withStock(10)->create();

    if ($model instanceof SalesOrder) {
        $order = SalesOrder::factory()->for($organization)->create();

        $order->items()->create([
            'product_id' => $product->id,
            'number' => 1,
            'quantity' => 5,
            'unit_price' => $product->sale_price,
            'subtotal' => $product->sale_price->multiply(5),
            'organization_id' => $user->organization_id,
        ]);
    } else {
        $order = PurchaseOrder::factory()->for($organization)->create();

        $order->items()->create([
            'product_id' => $product->id,
            'number' => 1,
            'quantity' => 5,
            'unit_price' => $product->cost_price,
            'subtotal' => $product->cost_price->multiply(5),
            'organization_id' => $user->organization_id,
        ]);
    }

    return $order->fresh('items');
}
