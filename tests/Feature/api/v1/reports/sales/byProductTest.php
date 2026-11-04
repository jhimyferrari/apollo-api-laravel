<?php

use App\Enum\PermissionType;
use App\Enum\Status\OrderStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\Seller;
use App\Models\User;
use App\ValueObjects\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('GET api/v1/reports/sales/by-product', function () {
    test('Non logged user cannot access sales by product report', function () {
        $response = $this->getJson(route('v1.reports.sales.by-product'));
        $response->assertUnauthorized();
    });

    test('Logged user without permission cannot access sales by product report', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user, [PermissionType::PRODUCT_READ->value]);

        $response = $this->getJson(route('v1.reports.sales.by-product'));
        $response->assertForbidden();
    });

    test('Logged user with valid permission gets sales by product report with multi-tenant isolation', function () {
        $organization = Organization::factory()->create();
        $user = User::factory()->for($organization)->create();

        $client = Client::factory()->for($organization)->create();
        $seller = Seller::factory()->for($organization)->create();

        $brandA = Brand::factory()->for($organization)->create();
        $product1 = Product::factory()->for($organization)->create([
            'brand_id' => $brandA->id,
            'sale_price' => Money::fromDecimal('20.0000'),
        ]);

        $product2 = Product::factory()->for($organization)->create([
            'sale_price' => Money::fromDecimal('50.0000'),
        ]);

        // Other organization data
        $otherOrg = Organization::factory()->create();
        $otherOrder = SalesOrder::factory()->for($otherOrg)->create([
            'status' => OrderStatus::Confirmed,
            'confirmed_at' => now(),
            'total' => Money::fromDecimal('500.0000'),
        ]);
        $otherProduct = Product::factory()->for($otherOrg)->create();
        $otherOrder->items()->create([
            'organization_id' => $otherOrg->id,
            'product_id' => $otherProduct->id,
            'number' => 1,
            'quantity' => 10,
            'unit_price' => Money::fromDecimal('50.0000'),
            'subtotal' => Money::fromDecimal('500.0000'),
        ]);

        // Confirmed order 1
        $order1 = SalesOrder::factory()->for($organization)->create([
            'client_id' => $client->id,
            'seller_id' => $seller->id,
            'status' => OrderStatus::Confirmed,
            'confirmed_at' => now(),
            'total' => Money::fromDecimal('140.0000'),
        ]);
        $order1->items()->create([
            'organization_id' => $organization->id,
            'product_id' => $product1->id,
            'number' => 1,
            'quantity' => 2,
            'unit_price' => Money::fromDecimal('20.0000'),
            'subtotal' => Money::fromDecimal('40.0000'),
        ]);
        $order1->items()->create([
            'organization_id' => $organization->id,
            'product_id' => $product2->id,
            'number' => 2,
            'quantity' => 2,
            'unit_price' => Money::fromDecimal('50.0000'),
            'subtotal' => Money::fromDecimal('100.0000'),
        ]);

        // Confirmed order 2
        $order2 = SalesOrder::factory()->for($organization)->create([
            'client_id' => $client->id,
            'seller_id' => $seller->id,
            'status' => OrderStatus::Confirmed,
            'confirmed_at' => now(),
            'total' => Money::fromDecimal('60.0000'),
        ]);
        $order2->items()->create([
            'organization_id' => $organization->id,
            'product_id' => $product1->id,
            'number' => 1,
            'quantity' => 3,
            'unit_price' => Money::fromDecimal('20.0000'),
            'subtotal' => Money::fromDecimal('60.0000'),
        ]);

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_READ->value]);

        $response = $this->getJson(route('v1.reports.sales.by-product'));

        $response->assertOk();
        $response->assertJsonStructure([
            'totals' => [
                'distinct_products_count',
                'total_quantity',
                'total_amount',
                'total_amount_formatted',
            ],
            'data',
            'meta',
            'links',
        ]);

        $json = $response->json();
        expect($json['totals']['distinct_products_count'])->toBe(2)
            ->and($json['totals']['total_quantity'])->toBe('7')
            ->and($json['totals']['total_amount'])->toBe('200.0000')
            ->and(count($json['data']))->toBe(2);

        $prod1Data = collect($json['data'])->firstWhere('product_id', $product1->id);
        expect($prod1Data)->not->toBeNull()
            ->and($prod1Data['quantity_sold'])->toBe('5')
            ->and($prod1Data['total_amount'])->toBe('100.0000')
            ->and($prod1Data['average_unit_price'])->toBe('20.0000')
            ->and($prod1Data['orders_count'])->toBe(2)
            ->and($prod1Data['brand']['id'])->toBe($brandA->id);

        $prodIds = collect($json['data'])->pluck('product_id');
        expect($prodIds)->not->toContain($otherProduct->id);
    });

    test('Can filter sales by product report by brand and category', function () {
        $organization = Organization::factory()->create();
        $user = User::factory()->for($organization)->create();

        $brand1 = Brand::factory()->for($organization)->create();
        $brand2 = Brand::factory()->for($organization)->create();

        $category1 = Category::factory()->for($organization)->create();

        $product1 = Product::factory()->for($organization)->create(['brand_id' => $brand1->id]);
        $product1->categories()->attach($category1);

        $product2 = Product::factory()->for($organization)->create(['brand_id' => $brand2->id]);

        $order = SalesOrder::factory()->for($organization)->create([
            'status' => OrderStatus::Confirmed,
            'confirmed_at' => now(),
            'total' => Money::fromDecimal('200.0000'),
        ]);
        $order->items()->create([
            'organization_id' => $organization->id,
            'product_id' => $product1->id,
            'number' => 1,
            'quantity' => 1,
            'unit_price' => Money::fromDecimal('100.0000'),
            'subtotal' => Money::fromDecimal('100.0000'),
        ]);
        $order->items()->create([
            'organization_id' => $organization->id,
            'product_id' => $product2->id,
            'number' => 2,
            'quantity' => 1,
            'unit_price' => Money::fromDecimal('100.0000'),
            'subtotal' => Money::fromDecimal('100.0000'),
        ]);

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_READ->value]);

        // Filter by category
        $response = $this->getJson(route('v1.reports.sales.by-product', [
            'category_id' => $category1->id,
        ]));
        $response->assertOk();
        $json = $response->json();
        expect($json['totals']['distinct_products_count'])->toBe(1)
            ->and($json['data'][0]['product_id'])->toBe($product1->id);

        // Filter by brand
        $response = $this->getJson(route('v1.reports.sales.by-product', [
            'brand_id' => $brand2->id,
        ]));
        $response->assertOk();
        $json = $response->json();
        expect($json['totals']['distinct_products_count'])->toBe(1)
            ->and($json['data'][0]['product_id'])->toBe($product2->id);
    });
});
