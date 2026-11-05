<?php

use App\Enum\PermissionType;
use App\Enum\Status\OrderStatus;
use App\Enum\Status\PaymentStatus;
use App\Models\Organization;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\ValueObjects\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('GET api/v1/reports/purchases/summary', function () {
    test('Non logged user cannot access purchase summary report', function () {
        $response = $this->getJson(route('v1.reports.purchases.summary'));
        $response->assertUnauthorized();
    });

    test('Logged user without permission cannot access purchase summary report', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_READ->value]);

        $response = $this->getJson(route('v1.reports.purchases.summary'));
        $response->assertForbidden();
    });

    test('Logged user with valid permission gets purchase summary report with multi-tenant isolation', function () {
        $organization = Organization::factory()->create();
        $user = User::factory()->for($organization)->create();

        $supplier = Supplier::factory()->for($organization)->create();
        $product = Product::factory()->for($organization)->create(['cost_price' => Money::fromDecimal('40.0000')]);

        // Other organization data - must not appear
        $otherOrg = Organization::factory()->create();
        $otherOrder = PurchaseOrder::factory()->for($otherOrg)->create([
            'status' => OrderStatus::Confirmed,
            'confirmed_at' => now(),
            'total' => Money::fromDecimal('888.0000'),
        ]);
        $otherOrder->items()->create([
            'organization_id' => $otherOrg->id,
            'product_id' => Product::factory()->for($otherOrg)->create()->id,
            'number' => 1,
            'quantity' => 10,
            'unit_price' => Money::fromDecimal('88.8000'),
            'subtotal' => Money::fromDecimal('888.0000'),
        ]);

        // Current organization confirmed orders
        $order1 = PurchaseOrder::factory()->for($organization)->create([
            'supplier_id' => $supplier->id,
            'status' => OrderStatus::Confirmed,
            'payment_status' => PaymentStatus::Paid,
            'confirmed_at' => now(),
            'total' => Money::fromDecimal('80.0000'),
        ]);
        $order1->items()->create([
            'organization_id' => $organization->id,
            'product_id' => $product->id,
            'number' => 1,
            'quantity' => 2,
            'unit_price' => Money::fromDecimal('40.0000'),
            'subtotal' => Money::fromDecimal('80.0000'),
        ]);

        $order2 = PurchaseOrder::factory()->for($organization)->create([
            'supplier_id' => $supplier->id,
            'status' => OrderStatus::Confirmed,
            'payment_status' => PaymentStatus::Pending,
            'confirmed_at' => now(),
            'total' => Money::fromDecimal('120.0000'),
        ]);
        $order2->items()->create([
            'organization_id' => $organization->id,
            'product_id' => $product->id,
            'number' => 1,
            'quantity' => 3,
            'unit_price' => Money::fromDecimal('40.0000'),
            'subtotal' => Money::fromDecimal('120.0000'),
        ]);

        // Draft order (should not be in confirmed totals)
        PurchaseOrder::factory()->for($organization)->create([
            'supplier_id' => $supplier->id,
            'status' => OrderStatus::Draft,
            'total' => Money::fromDecimal('200.0000'),
        ]);

        Sanctum::actingAs($user, [PermissionType::PURCHASE_ORDER_READ->value]);

        $response = $this->getJson(route('v1.reports.purchases.summary'));

        $response->assertOk();
        $response->assertJsonStructure([
            'totals' => [
                'orders_count',
                'total_amount',
                'total_amount_formatted',
                'average_ticket',
                'average_ticket_formatted',
                'total_items_quantity',
                'by_status',
                'by_payment_status',
            ],
            'data',
            'meta',
            'links',
        ]);

        $json = $response->json();
        expect($json['totals']['orders_count'])->toBe(2)
            ->and($json['totals']['total_amount'])->toBe('200.0000')
            ->and($json['totals']['average_ticket'])->toBe('100.0000')
            ->and($json['totals']['total_items_quantity'])->toBe('5')
            ->and(count($json['data']))->toBe(2);

        $ids = collect($json['data'])->pluck('id');
        expect($ids)->toContain($order1->id)
            ->and($ids)->toContain($order2->id)
            ->and($ids)->not->toContain($otherOrder->id);
    });

    test('Can filter purchase summary report by supplier and date range', function () {
        $organization = Organization::factory()->create();
        $user = User::factory()->for($organization)->create();

        $supplier1 = Supplier::factory()->for($organization)->create();
        $supplier2 = Supplier::factory()->for($organization)->create();

        PurchaseOrder::factory()->for($organization)->create([
            'supplier_id' => $supplier1->id,
            'status' => OrderStatus::Confirmed,
            'confirmed_at' => now()->subDays(10),
            'total' => Money::fromDecimal('100.0000'),
        ]);

        $orderToFind = PurchaseOrder::factory()->for($organization)->create([
            'supplier_id' => $supplier1->id,
            'status' => OrderStatus::Confirmed,
            'confirmed_at' => now()->subDay(),
            'total' => Money::fromDecimal('250.0000'),
        ]);

        PurchaseOrder::factory()->for($organization)->create([
            'supplier_id' => $supplier2->id,
            'status' => OrderStatus::Confirmed,
            'confirmed_at' => now()->subDay(),
            'total' => Money::fromDecimal('300.0000'),
        ]);

        Sanctum::actingAs($user, [PermissionType::PURCHASE_ORDER_READ->value]);

        $response = $this->getJson(route('v1.reports.purchases.summary', [
            'supplier_id' => $supplier1->id,
            'start_date' => now()->subDays(3)->toDateString(),
            'end_date' => now()->toDateString(),
        ]));

        $response->assertOk();
        $json = $response->json();
        expect($json['totals']['orders_count'])->toBe(1)
            ->and($json['totals']['total_amount'])->toBe('250.0000')
            ->and($json['data'][0]['id'])->toBe($orderToFind->id);
    });
});
