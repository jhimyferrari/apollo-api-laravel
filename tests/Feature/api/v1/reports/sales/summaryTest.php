<?php

use App\Enum\PermissionType;
use App\Enum\Status\OrderStatus;
use App\Enum\Status\PaymentStatus;
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

describe('GET api/v1/reports/sales/summary', function () {
    test('Non logged user cannot access sales summary report', function () {
        $response = $this->getJson(route('v1.reports.sales.summary'));
        $response->assertUnauthorized();
    });

    test('Logged user without permission cannot access sales summary report', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user, [PermissionType::PRODUCT_READ->value]);

        $response = $this->getJson(route('v1.reports.sales.summary'));
        $response->assertForbidden();
    });

    test('Logged user with valid permission can get sales summary report with multi-tenant isolation', function () {
        $organization = Organization::factory()->create();
        $user = User::factory()->for($organization)->create();

        $client = Client::factory()->for($organization)->create();
        $seller = Seller::factory()->for($organization)->create();
        $product = Product::factory()->for($organization)->create(['sale_price' => Money::fromDecimal('50.0000')]);

        // Other organization data - must not appear or affect totals
        $otherOrg = Organization::factory()->create();
        $otherOrder = SalesOrder::factory()->for($otherOrg)->create([
            'status' => OrderStatus::Confirmed,
            'confirmed_at' => now(),
            'total' => Money::fromDecimal('999.0000'),
        ]);
        $otherOrder->items()->create([
            'organization_id' => $otherOrg->id,
            'product_id' => Product::factory()->for($otherOrg)->create()->id,
            'number' => 1,
            'quantity' => 10,
            'unit_price' => Money::fromDecimal('99.9000'),
            'subtotal' => Money::fromDecimal('999.0000'),
        ]);

        // Current organization confirmed orders
        $order1 = SalesOrder::factory()->for($organization)->create([
            'client_id' => $client->id,
            'seller_id' => $seller->id,
            'status' => OrderStatus::Confirmed,
            'payment_status' => PaymentStatus::Paid,
            'confirmed_at' => now(),
            'total' => Money::fromDecimal('100.0000'),
        ]);
        $order1->items()->create([
            'organization_id' => $organization->id,
            'product_id' => $product->id,
            'number' => 1,
            'quantity' => 2,
            'unit_price' => Money::fromDecimal('50.0000'),
            'subtotal' => Money::fromDecimal('100.0000'),
        ]);

        $order2 = SalesOrder::factory()->for($organization)->create([
            'client_id' => $client->id,
            'seller_id' => $seller->id,
            'status' => OrderStatus::Confirmed,
            'payment_status' => PaymentStatus::Pending,
            'confirmed_at' => now(),
            'total' => Money::fromDecimal('200.0000'),
        ]);
        $order2->items()->create([
            'organization_id' => $organization->id,
            'product_id' => $product->id,
            'number' => 1,
            'quantity' => 4,
            'unit_price' => Money::fromDecimal('50.0000'),
            'subtotal' => Money::fromDecimal('200.0000'),
        ]);

        // Draft order (should not be in confirmed totals)
        SalesOrder::factory()->for($organization)->create([
            'client_id' => $client->id,
            'seller_id' => $seller->id,
            'status' => OrderStatus::Draft,
            'total' => Money::fromDecimal('50.0000'),
        ]);

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_READ->value]);

        $response = $this->getJson(route('v1.reports.sales.summary'));

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
            ->and($json['totals']['total_amount'])->toBe('300.0000')
            ->and($json['totals']['average_ticket'])->toBe('150.0000')
            ->and($json['totals']['total_items_quantity'])->toBe('6')
            ->and(count($json['data']))->toBe(2);

        // Verify order items inside data
        $ids = collect($json['data'])->pluck('id');
        expect($ids)->toContain($order1->id)
            ->and($ids)->toContain($order2->id)
            ->and($ids)->not->toContain($otherOrder->id);
    });

    test('Can filter sales summary report by date range', function () {
        $organization = Organization::factory()->create();
        $user = User::factory()->for($organization)->create();

        SalesOrder::factory()->for($organization)->create([
            'status' => OrderStatus::Confirmed,
            'confirmed_at' => now()->subDays(10),
            'total' => Money::fromDecimal('100.0000'),
        ]);

        $recentOrder = SalesOrder::factory()->for($organization)->create([
            'status' => OrderStatus::Confirmed,
            'confirmed_at' => now()->subDay(),
            'total' => Money::fromDecimal('200.0000'),
        ]);

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_READ->value]);

        $response = $this->getJson(route('v1.reports.sales.summary', [
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->toDateString(),
        ]));

        $response->assertOk();
        $json = $response->json();
        expect($json['totals']['orders_count'])->toBe(1)
            ->and($json['totals']['total_amount'])->toBe('200.0000')
            ->and($json['data'][0]['id'])->toBe($recentOrder->id);
    });

    test('Can filter sales summary report by status', function () {
        $organization = Organization::factory()->create();
        $user = User::factory()->for($organization)->create();

        $draftOrder = SalesOrder::factory()->for($organization)->create([
            'status' => OrderStatus::Draft,
            'created_at' => now(),
            'total' => Money::fromDecimal('80.0000'),
        ]);

        SalesOrder::factory()->for($organization)->create([
            'status' => OrderStatus::Confirmed,
            'confirmed_at' => now(),
            'total' => Money::fromDecimal('150.0000'),
        ]);

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_READ->value]);

        $response = $this->getJson(route('v1.reports.sales.summary', [
            'status' => OrderStatus::Draft->value,
            'date_field' => 'created_at',
        ]));

        $response->assertOk();
        $json = $response->json();
        expect($json['totals']['orders_count'])->toBe(1)
            ->and($json['totals']['total_amount'])->toBe('80.0000')
            ->and($json['data'][0]['id'])->toBe($draftOrder->id);
    });
});
