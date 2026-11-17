<?php

use App\Enum\PermissionType;
use App\Enum\Status\OrderStatus;
use App\Enum\Status\PaymentStatus;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('PATCH api/v1/sales-orders/{salesOrder}/payment-status', function () {
    test('Logged user updates payment status from Pending to Paid on a Draft order', function () {
        $this->travelTo('2026-11-16 12:00:00');
        $user = User::factory()->create();
        $order = SalesOrder::factory()->for($user->organization)->create([
            'status' => OrderStatus::Draft,
            'payment_status' => PaymentStatus::Pending,
        ]);

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_UPDATE->value]);

        $response = $this->patchJson(route('v1.sales-orders.payment-status', $order), [
            'payment_status' => PaymentStatus::Paid->value,
        ]);

        $response->assertNoContent();

        $this->assertDatabaseHas('sales_orders', [
            'id' => $order->id,
            'payment_status' => PaymentStatus::Paid->value,
            'paid_at' => '2026-11-16 12:00:00',
            'refunded_at' => null,
        ]);
    });

    test('Logged user updates payment status from Pending to Paid on a Confirmed order', function () {
        $this->travelTo('2026-11-16 14:30:00');
        $user = User::factory()->create();
        $order = SalesOrder::factory()->for($user->organization)->create([
            'status' => OrderStatus::Confirmed,
            'payment_status' => PaymentStatus::Pending,
        ]);

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_UPDATE->value]);

        $response = $this->patchJson(route('v1.sales-orders.payment-status', $order), [
            'payment_status' => PaymentStatus::Paid->value,
        ]);

        $response->assertNoContent();

        $this->assertDatabaseHas('sales_orders', [
            'id' => $order->id,
            'payment_status' => PaymentStatus::Paid->value,
            'paid_at' => '2026-11-16 14:30:00',
            'refunded_at' => null,
        ]);
    });

    test('Logged user reverts payment status from Paid to Pending on a Confirmed order', function () {
        $user = User::factory()->create();
        $order = SalesOrder::factory()->for($user->organization)->paid()->create([
            'status' => OrderStatus::Confirmed,
        ]);

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_UPDATE->value]);

        $response = $this->patchJson(route('v1.sales-orders.payment-status', $order), [
            'payment_status' => PaymentStatus::Pending->value,
        ]);

        $response->assertNoContent();

        $this->assertDatabaseHas('sales_orders', [
            'id' => $order->id,
            'payment_status' => PaymentStatus::Pending->value,
            'paid_at' => null,
            'refunded_at' => null,
        ]);
    });

    test('Logged user refunds payment status from Paid to Refunded on a Cancelled order', function () {
        $paidAt = now()->subDay()->startOfSecond();
        $this->travelTo('2026-11-16 18:00:00');
        $user = User::factory()->create();
        $order = SalesOrder::factory()->for($user->organization)->create([
            'status' => OrderStatus::Cancelled,
            'payment_status' => PaymentStatus::Paid,
            'paid_at' => $paidAt,
        ]);

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_UPDATE->value]);

        $response = $this->patchJson(route('v1.sales-orders.payment-status', $order), [
            'payment_status' => PaymentStatus::Refunded->value,
        ]);

        $response->assertNoContent();

        $this->assertDatabaseHas('sales_orders', [
            'id' => $order->id,
            'payment_status' => PaymentStatus::Refunded->value,
            'paid_at' => $paidAt->toDateTimeString(),
            'refunded_at' => '2026-11-16 18:00:00',
        ]);
    });

    test('Non logged user receives unauthorized', function () {
        $order = SalesOrder::factory()->create();

        $response = $this->patchJson(route('v1.sales-orders.payment-status', $order), [
            'payment_status' => PaymentStatus::Paid->value,
        ]);

        $response->assertUnauthorized();
    });

    test('Logged user without permission receives forbidden', function () {
        $user = User::factory()->create();
        $order = SalesOrder::factory()->for($user->organization)->create();

        Sanctum::actingAs($user);

        $response = $this->patchJson(route('v1.sales-orders.payment-status', $order), [
            'payment_status' => PaymentStatus::Paid->value,
        ]);

        $response->assertForbidden();
    });

    test('Logged user cannot update payment status of an order from another organization', function () {
        $user = User::factory()->create();
        $otherOrder = SalesOrder::factory()->create();

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_UPDATE->value]);

        $response = $this->patchJson(route('v1.sales-orders.payment-status', $otherOrder), [
            'payment_status' => PaymentStatus::Paid->value,
        ]);

        $response->assertNotFound();
    });

    test('Returns validation error when payment_status is missing or invalid', function (array $payload) {
        $user = User::factory()->create();
        $order = SalesOrder::factory()->for($user->organization)->create();

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_UPDATE->value]);

        $response = $this->patchJson(route('v1.sales-orders.payment-status', $order), $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('payment_status', 'error.fields');
    })->with([
        'empty payload' => [[]],
        'invalid enum value' => [['payment_status' => 'invalid']],
    ]);

    test('Returns unprocessable when transition is not allowed', function (
        OrderStatus $orderStatus,
        PaymentStatus $fromPaymentStatus,
        PaymentStatus $targetPaymentStatus,
    ) {
        $user = User::factory()->create();
        $order = SalesOrder::factory()->for($user->organization)->create([
            'status' => $orderStatus,
            'payment_status' => $fromPaymentStatus,
        ]);

        Sanctum::actingAs($user, [PermissionType::SALES_ORDER_UPDATE->value]);

        $response = $this->patchJson(route('v1.sales-orders.payment-status', $order), [
            'payment_status' => $targetPaymentStatus->value,
        ]);

        $response->assertUnprocessable();
        expect($order->fresh()->payment_status)->toBe($fromPaymentStatus);
    })->with([
        'confirmed paid to refunded' => [OrderStatus::Confirmed, PaymentStatus::Paid, PaymentStatus::Refunded],
        'confirmed pending to cancelled' => [OrderStatus::Confirmed, PaymentStatus::Pending, PaymentStatus::Cancelled],
        'cancelled paid to pending' => [OrderStatus::Cancelled, PaymentStatus::Paid, PaymentStatus::Pending],
        'cancelled cancelled to paid' => [OrderStatus::Cancelled, PaymentStatus::Cancelled, PaymentStatus::Paid],
    ]);
});
