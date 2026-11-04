<?php

use App\Enum\PermissionType;
use App\Enum\Status\OrderStatus;
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
                'total_amount',
                'total_amount_formatted',
            ],
            'data',
            'meta',
            'links',
        ]);

        $json = $response->json();
        expect($json['totals']['active_sellers_count'])->toBe(2)
            ->and($json['totals']['orders_count'])->toBe(3)
            ->and($json['totals']['total_amount'])->toBe('400.0000')
            ->and(count($json['data']))->toBe(2);

        $seller1Data = collect($json['data'])->firstWhere('seller_id', $seller1->id);
        expect($seller1Data)->not->toBeNull()
            ->and($seller1Data['orders_count'])->toBe(2)
            ->and($seller1Data['total_amount'])->toBe('300.0000')
            ->and($seller1Data['average_ticket'])->toBe('150.0000')
            ->and($seller1Data['percentage_of_total'])->toEqual(75.0);

        $seller2Data = collect($json['data'])->firstWhere('seller_id', $seller2->id);
        expect($seller2Data)->not->toBeNull()
            ->and($seller2Data['orders_count'])->toBe(1)
            ->and($seller2Data['total_amount'])->toBe('100.0000')
            ->and($seller2Data['average_ticket'])->toBe('100.0000')
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
            ->and($json['totals']['total_amount'])->toBe('100.0000')
            ->and(count($json['data']))->toBe(1)
            ->and($json['data'][0]['seller_id'])->toBe($seller1->id);
    });
});
