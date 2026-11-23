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
use App\Services\SalesOrderService;
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
                'pending_refund' => ['count', 'total', 'total_formatted'],
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

    test('Can filter sales summary report with status=all', function () {
        $organization = Organization::factory()->create();
        $user = User::factory()->for($organization)->create();

        SalesOrder::factory()->for($organization)->create([
            'status' => OrderStatus::Draft,
            'total' => Money::fromDecimal('50.0000'),
        ]);
        SalesOrder::factory()->for($organization)->create([
            'status' => OrderStatus::Confirmed,
            'confirmed_at' => now(),
            'total' => Money::fromDecimal('150.0000'),
        ]);

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_READ->value]);

        $response = $this->getJson(route('v1.reports.sales.summary', ['status' => 'all']));

        $response->assertOk();
        expect($response->json('totals.orders_count'))->toBe(2)
            ->and($response->json('totals.total_amount'))->toBe('200.0000');
    });

    test('Fails with 422 when filtering by client_id or seller_id from another organization', function (string $field) {
        $user = User::factory()->create();
        $otherOrg = Organization::factory()->create();
        $foreignId = $field === 'client_id'
            ? Client::factory()->for($otherOrg)->create()->id
            : Seller::factory()->for($otherOrg)->create()->id;

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_READ->value]);

        $response = $this->getJson(route('v1.reports.sales.summary', [$field => $foreignId]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors($field, 'error.fields');
    })->with(['client_id', 'seller_id']);

    test('Infers cancelled order status when filtering by refunded or cancelled payment status without explicit status', function () {
        $organization = Organization::factory()->create();
        $user = User::factory()->for($organization)->create();

        $refundedOrder = SalesOrder::factory()->for($organization)->cancelled()->refunded()->create([
            'paid_at' => now()->subDays(2),
            'refunded_at' => now()->subDay(),
            'total' => Money::fromDecimal('120.0000'),
        ]);

        $cancelledPaymentOrder = SalesOrder::factory()->for($organization)->cancelled()->paymentCancelled()->create([
            'total' => Money::fromDecimal('80.0000'),
        ]);

        SalesOrder::factory()->for($organization)->paid()->create([
            'status' => OrderStatus::Confirmed,
            'confirmed_at' => now(),
            'total' => Money::fromDecimal('300.0000'),
        ]);

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_READ->value]);

        $refundedResponse = $this->getJson(route('v1.reports.sales.summary', [
            'payment_status' => PaymentStatus::Refunded->value,
        ]));

        $refundedResponse->assertOk();
        $refundedJson = $refundedResponse->json();
        expect($refundedJson['totals']['orders_count'])->toBe(1)
            ->and($refundedJson['totals']['total_amount'])->toBe('120.0000')
            ->and($refundedJson['data'][0]['id'])->toBe($refundedOrder->id)
            ->and($refundedJson['data'][0]['paid_at'])->not->toBeNull()
            ->and($refundedJson['data'][0]['refunded_at'])->not->toBeNull()
            ->and($refundedJson['data'][0]['cancelled_at'])->not->toBeNull();

        $cancelledResponse = $this->getJson(route('v1.reports.sales.summary', [
            'payment_status' => PaymentStatus::Cancelled->value,
        ]));

        $cancelledResponse->assertOk();
        expect($cancelledResponse->json('totals.orders_count'))->toBe(1)
            ->and($cancelledResponse->json('data.0.id'))->toBe($cancelledPaymentOrder->id);
    });

    test('Can filter sales summary report by paid_at, refunded_at and cancelled_at date fields', function () {
        $organization = Organization::factory()->create();
        $user = User::factory()->for($organization)->create();

        $paidRecently = SalesOrder::factory()->for($organization)->create([
            'status' => OrderStatus::Confirmed,
            'payment_status' => PaymentStatus::Paid,
            'confirmed_at' => now()->subDays(15),
            'paid_at' => now()->subDay(),
            'total' => Money::fromDecimal('150.0000'),
        ]);

        SalesOrder::factory()->for($organization)->create([
            'status' => OrderStatus::Confirmed,
            'payment_status' => PaymentStatus::Paid,
            'confirmed_at' => now()->subDays(15),
            'paid_at' => now()->subDays(10),
            'total' => Money::fromDecimal('50.0000'),
        ]);

        $refundedRecently = SalesOrder::factory()->for($organization)->cancelled()->create([
            'payment_status' => PaymentStatus::Refunded,
            'paid_at' => now()->subDays(12),
            'refunded_at' => now()->subDay(),
            'cancelled_at' => now()->subDays(10),
            'total' => Money::fromDecimal('90.0000'),
        ]);

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_READ->value]);

        $paidRes = $this->getJson(route('v1.reports.sales.summary', [
            'date_field' => 'paid_at',
            'start_date' => now()->subDays(3)->toDateString(),
            'end_date' => now()->toDateString(),
        ]));

        $paidRes->assertOk();
        expect($paidRes->json('totals.orders_count'))->toBe(1)
            ->and($paidRes->json('data.0.id'))->toBe($paidRecently->id);

        $refundedRes = $this->getJson(route('v1.reports.sales.summary', [
            'payment_status' => PaymentStatus::Refunded->value,
            'date_field' => 'refunded_at',
            'start_date' => now()->subDays(3)->toDateString(),
            'end_date' => now()->toDateString(),
        ]));

        $refundedRes->assertOk();
        expect($refundedRes->json('totals.orders_count'))->toBe(1)
            ->and($refundedRes->json('data.0.id'))->toBe($refundedRecently->id);

        $cancelledRes = $this->getJson(route('v1.reports.sales.summary', [
            'status' => OrderStatus::Cancelled->value,
            'date_field' => 'cancelled_at',
            'start_date' => now()->subDays(15)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
        ]));

        $cancelledRes->assertOk();
        expect($cancelledRes->json('totals.orders_count'))->toBe(1)
            ->and($cancelledRes->json('data.0.id'))->toBe($refundedRecently->id);
    });

    test('Calculates pending_refund totals accurately with multi-tenant isolation', function () {
        $organization = Organization::factory()->create();
        $user = User::factory()->for($organization)->create();

        // Cancelled confirmed order still paid -> pending refund
        SalesOrder::factory()->for($organization)->cancelled()->paid()->create([
            'confirmed_at' => now()->subDay(),
            'cancelled_at' => now()->subHours(2),
            'total' => Money::fromDecimal('100.0000'),
        ]);

        // Cancelled draft order still paid (confirmed_at is null) -> still pending refund
        SalesOrder::factory()->for($organization)->cancelled()->paid()->create([
            'confirmed_at' => null,
            'cancelled_at' => now()->subHour(),
            'total' => Money::fromDecimal('50.0000'),
        ]);

        // Cancelled and already refunded -> must not count
        SalesOrder::factory()->for($organization)->cancelled()->refunded()->create([
            'cancelled_at' => now(),
            'total' => Money::fromDecimal('200.0000'),
        ]);

        // Confirmed and paid -> must not count
        SalesOrder::factory()->for($organization)->paid()->create([
            'status' => OrderStatus::Confirmed,
            'confirmed_at' => now(),
            'total' => Money::fromDecimal('300.0000'),
        ]);

        // Other org cancelled and paid -> must not count
        $otherOrg = Organization::factory()->create();
        SalesOrder::factory()->for($otherOrg)->cancelled()->paid()->create([
            'cancelled_at' => now(),
            'total' => Money::fromDecimal('999.0000'),
        ]);

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_READ->value]);

        $response = $this->getJson(route('v1.reports.sales.summary', [
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->toDateString(),
        ]));

        $response->assertOk();
        $json = $response->json();

        expect($json['totals']['orders_count'])->toBe(1)
            ->and($json['totals']['pending_refund']['count'])->toBe(2)
            ->and($json['totals']['pending_refund']['total'])->toBe('150.0000');
    });

    test('Maintains breakdown symmetry for by_status and by_payment_status', function () {
        $organization = Organization::factory()->create();
        $user = User::factory()->for($organization)->create();

        SalesOrder::factory()->for($organization)->paid()->create([
            'status' => OrderStatus::Confirmed,
            'confirmed_at' => now(),
            'total' => Money::fromDecimal('100.0000'),
        ]);
        SalesOrder::factory()->for($organization)->create([
            'status' => OrderStatus::Confirmed,
            'payment_status' => PaymentStatus::Pending,
            'confirmed_at' => now(),
            'total' => Money::fromDecimal('200.0000'),
        ]);
        SalesOrder::factory()->for($organization)->paid()->create([
            'status' => OrderStatus::Draft,
            'total' => Money::fromDecimal('50.0000'),
        ]);

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_READ->value]);

        $response = $this->getJson(route('v1.reports.sales.summary', [
            'status' => 'all',
            'payment_status' => PaymentStatus::Paid->value,
        ]));

        $response->assertOk();
        $json = $response->json();

        // by_status respects payment_status=paid
        expect($json['totals']['by_status']['confirmed']['count'])->toBe(1)
            ->and($json['totals']['by_status']['confirmed']['total'])->toBe('100.0000')
            ->and($json['totals']['by_status']['draf']['count'])->toBe(1)
            ->and($json['totals']['by_status']['draf']['total'])->toBe('50.0000')
            // by_payment_status keeps comparison across payment statuses for status=all
            ->and($json['totals']['by_payment_status']['paid']['count'])->toBe(2)
            ->and($json['totals']['by_payment_status']['pending']['count'])->toBe(1);
    });

    test('Reopening a paid sales order does not double count paid orders in summary report', function () {
        $organization = Organization::factory()->create();
        $user = User::factory()->for($organization)->create();
        $product = Product::factory()->for($organization)->create([
            'sale_price' => Money::fromDecimal('100.0000'),
        ]);

        $order = SalesOrder::factory()->for($organization)->paid()->create([
            'status' => OrderStatus::Confirmed,
            'confirmed_at' => now(),
            'total' => Money::fromDecimal('100.0000'),
        ]);
        $order->items()->create([
            'organization_id' => $organization->id,
            'product_id' => $product->id,
            'number' => 1,
            'quantity' => 1,
            'unit_price' => Money::fromDecimal('100.0000'),
            'subtotal' => Money::fromDecimal('100.0000'),
        ]);

        $reopened = app(SalesOrderService::class)->recreateFrom($order, 'Ajuste');

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_READ->value]);

        $response = $this->getJson(route('v1.reports.sales.summary', [
            'status' => 'all',
            'payment_status' => PaymentStatus::Paid->value,
        ]));

        $response->assertOk();
        $json = $response->json();

        expect($json['totals']['orders_count'])->toBe(1)
            ->and($json['totals']['total_amount'])->toBe('100.0000')
            ->and($json['data'][0]['id'])->toBe($reopened['order']->id)
            ->and($json['totals']['pending_refund']['count'])->toBe(0);
    });
});
