<?php

use App\Enum\PermissionType;
use App\Enum\Status\OrderStatus;
use App\Enum\Status\PaymentStatus;
use App\Models\Client;
use App\Models\Organization;
use App\Models\SalesOrder;
use App\Models\Seller;
use App\Models\User;
use App\ValueObjects\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('GET api/v1/reports/sales/by-seller', function () {
    test('Non logged user cannot access sales by seller report', function () {
        $response = $this->getJson(route('v1.reports.sales.by-seller'));
        $response->assertUnauthorized();
    });

    test('Logged user without permission cannot access sales by seller report', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user, [PermissionType::PRODUCT_READ->value]);

        $response = $this->getJson(route('v1.reports.sales.by-seller'));
        $response->assertForbidden();
    });

    test('Logged user with valid permission gets sales by seller report with multi-tenant isolation', function () {
        $organization = Organization::factory()->create();
        $user = User::factory()->for($organization)->create();

        $client = Client::factory()->for($organization)->create();
        $seller1 = Seller::factory()->for($organization)->create([
            'trade_name' => 'Vendedor Alfa',
        ]);
        $seller2 = Seller::factory()->for($organization)->create([
            'trade_name' => 'Vendedor Beta',
        ]);

        // Other organization seller and order
        $otherOrg = Organization::factory()->create();
        $otherSeller = Seller::factory()->for($otherOrg)->create();
        SalesOrder::factory()->for($otherOrg)->create([
            'seller_id' => $otherSeller->id,
            'status' => OrderStatus::Confirmed,
            'confirmed_at' => now(),
            'total' => Money::fromDecimal('999.0000'),
        ]);

        // Seller 1 orders
        SalesOrder::factory()->for($organization)->create([
            'client_id' => $client->id,
            'seller_id' => $seller1->id,
            'status' => OrderStatus::Confirmed,
            'confirmed_at' => now(),
            'total' => Money::fromDecimal('100.0000'),
        ]);
        SalesOrder::factory()->for($organization)->create([
            'client_id' => $client->id,
            'seller_id' => $seller1->id,
            'status' => OrderStatus::Confirmed,
            'confirmed_at' => now(),
            'total' => Money::fromDecimal('200.0000'),
        ]);

        // Seller 2 order
        SalesOrder::factory()->for($organization)->create([
            'client_id' => $client->id,
            'seller_id' => $seller2->id,
            'status' => OrderStatus::Confirmed,
            'confirmed_at' => now(),
            'total' => Money::fromDecimal('100.0000'),
        ]);

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_READ->value]);

        $response = $this->getJson(route('v1.reports.sales.by-seller'));

        $response->assertOk();
        $response->assertJsonStructure([
            'totals' => [
                'active_sellers_count',
                'orders_count',
                'total_amount' => ['amount', 'currency', 'formatted'],
            ],
            'data' => [
                '*' => [
                    'total_amount' => ['amount', 'currency', 'formatted'],
                    'average_ticket' => ['amount', 'currency', 'formatted'],
                ],
            ],
            'meta',
            'links',
        ]);

        $json = $response->json();
        expect($json['totals']['active_sellers_count'])->toBe(2)
            ->and($json['totals']['orders_count'])->toBe(3)
            ->and($json['totals']['total_amount']['amount'])->toBe('400.0000')
            ->and(count($json['data']))->toBe(2);

        $seller1Data = collect($json['data'])->firstWhere('seller_id', $seller1->id);
        expect($seller1Data)->not->toBeNull()
            ->and($seller1Data['orders_count'])->toBe(2)
            ->and($seller1Data['total_amount']['amount'])->toBe('300.0000')
            ->and($seller1Data['average_ticket']['amount'])->toBe('150.0000')
            ->and($seller1Data['percentage_of_total'])->toEqual(75.0);

        $seller2Data = collect($json['data'])->firstWhere('seller_id', $seller2->id);
        expect($seller2Data)->not->toBeNull()
            ->and($seller2Data['orders_count'])->toBe(1)
            ->and($seller2Data['total_amount']['amount'])->toBe('100.0000')
            ->and($seller2Data['average_ticket']['amount'])->toBe('100.0000')
            ->and($seller2Data['percentage_of_total'])->toEqual(25.0);

        $sellerIds = collect($json['data'])->pluck('seller_id');
        expect($sellerIds)->not->toContain($otherSeller->id);
    });

    test('Can filter sales by seller report by specific seller_id', function () {
        $organization = Organization::factory()->create();
        $user = User::factory()->for($organization)->create();

        $seller1 = Seller::factory()->for($organization)->create();
        $seller2 = Seller::factory()->for($organization)->create();

        SalesOrder::factory()->for($organization)->create([
            'seller_id' => $seller1->id,
            'status' => OrderStatus::Confirmed,
            'confirmed_at' => now(),
            'total' => Money::fromDecimal('100.0000'),
        ]);

        SalesOrder::factory()->for($organization)->create([
            'seller_id' => $seller2->id,
            'status' => OrderStatus::Confirmed,
            'confirmed_at' => now(),
            'total' => Money::fromDecimal('50.0000'),
        ]);

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_READ->value]);

        $response = $this->getJson(route('v1.reports.sales.by-seller', [
            'seller_id' => $seller1->id,
        ]));

        $response->assertOk();
        $json = $response->json();
        expect($json['totals']['active_sellers_count'])->toBe(1)
            ->and($json['totals']['orders_count'])->toBe(1)
            ->and($json['totals']['total_amount']['amount'])->toBe('100.0000')
            ->and(count($json['data']))->toBe(1)
            ->and($json['data'][0]['seller_id'])->toBe($seller1->id);
    });

    test('Can filter sales by seller report with status=all', function () {
        $organization = Organization::factory()->create();
        $user = User::factory()->for($organization)->create();
        $seller = Seller::factory()->for($organization)->create();

        SalesOrder::factory()->for($organization)->create([
            'seller_id' => $seller->id,
            'status' => OrderStatus::Draft,
            'total' => Money::fromDecimal('100.0000'),
        ]);

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_READ->value]);

        $response = $this->getJson(route('v1.reports.sales.by-seller', ['status' => 'all']));

        $response->assertOk();
        expect($response->json('totals.orders_count'))->toBe(1);
    });

    test('Fails with 422 when filtering by seller_id from another organization', function () {
        $user = User::factory()->create();
        $otherSeller = Seller::factory()->create();

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_READ->value]);

        $response = $this->getJson(route('v1.reports.sales.by-seller', [
            'seller_id' => $otherSeller->id,
        ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('seller_id', 'error.fields');
    });

    test('Can filter sales by seller report by payment_status and paid_at date_field', function () {
        $organization = Organization::factory()->create();
        $user = User::factory()->for($organization)->create();

        $seller1 = Seller::factory()->for($organization)->create();
        $seller2 = Seller::factory()->for($organization)->create();

        SalesOrder::factory()->for($organization)->create([
            'seller_id' => $seller1->id,
            'status' => OrderStatus::Confirmed,
            'payment_status' => PaymentStatus::Paid,
            'confirmed_at' => now()->subDays(10),
            'paid_at' => now()->subDay(),
            'total' => Money::fromDecimal('150.0000'),
        ]);

        SalesOrder::factory()->for($organization)->create([
            'seller_id' => $seller2->id,
            'status' => OrderStatus::Confirmed,
            'payment_status' => PaymentStatus::Pending,
            'confirmed_at' => now(),
            'total' => Money::fromDecimal('250.0000'),
        ]);

        SalesOrder::factory()->for($organization)->cancelled()->refunded()->create([
            'seller_id' => $seller1->id,
            'total' => Money::fromDecimal('75.0000'),
        ]);

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_READ->value]);

        $paidRes = $this->getJson(route('v1.reports.sales.by-seller', [
            'payment_status' => PaymentStatus::Paid->value,
            'date_field' => 'paid_at',
            'start_date' => now()->subDays(3)->toDateString(),
            'end_date' => now()->toDateString(),
        ]));

        $paidRes->assertOk();
        expect($paidRes->json('totals.active_sellers_count'))->toBe(1)
            ->and($paidRes->json('totals.total_amount.amount'))->toBe('150.0000')
            ->and($paidRes->json('data.0.seller_id'))->toBe($seller1->id);

        $pendingRes = $this->getJson(route('v1.reports.sales.by-seller', [
            'payment_status' => PaymentStatus::Pending->value,
        ]));

        $pendingRes->assertOk();
        expect($pendingRes->json('totals.active_sellers_count'))->toBe(1)
            ->and($pendingRes->json('totals.total_amount.amount'))->toBe('250.0000')
            ->and($pendingRes->json('data.0.seller_id'))->toBe($seller2->id);

        $refundedRes = $this->getJson(route('v1.reports.sales.by-seller', [
            'payment_status' => PaymentStatus::Refunded->value,
        ]));

        $refundedRes->assertOk();
        expect($refundedRes->json('totals.orders_count'))->toBe(1)
            ->and($refundedRes->json('totals.total_amount.amount'))->toBe('75.0000');
    });
});
