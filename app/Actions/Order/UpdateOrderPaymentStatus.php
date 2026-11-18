<?php

namespace App\Actions\Order;

use App\Enum\Status\OrderStatus;
use App\Enum\Status\PaymentStatus;
use App\Exceptions\InvalidStatusTransitionException;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;

class UpdateOrderPaymentStatus
{
    /**
     * Validate and apply a manual payment status transition for an order.
     *
     * @throws InvalidStatusTransitionException
     */
    public function execute(SalesOrder|PurchaseOrder $order, PaymentStatus $targetStatus): SalesOrder|PurchaseOrder
    {
        $currentStatus = $order->payment_status;

        if ($order->status === OrderStatus::Draft || $order->status === OrderStatus::Confirmed) {
            if ($currentStatus === PaymentStatus::Pending && $targetStatus === PaymentStatus::Paid) {
                $order->payment_status = PaymentStatus::Paid;
                $order->paid_at = now();
                $order->refunded_at = null;
                $order->save();

                return $order->refresh();
            }

            if ($currentStatus === PaymentStatus::Paid && $targetStatus === PaymentStatus::Pending) {
                $order->payment_status = PaymentStatus::Pending;
                $order->paid_at = null;
                $order->refunded_at = null;
                $order->save();

                return $order->refresh();
            }

            throw InvalidStatusTransitionException::make($currentStatus, $targetStatus);
        }

        if ($order->status === OrderStatus::Cancelled) {
            if ($currentStatus === PaymentStatus::Paid && $targetStatus === PaymentStatus::Refunded) {
                $order->payment_status = PaymentStatus::Refunded;
                $order->refunded_at = now();
                $order->save();

                return $order->refresh();
            }

            throw InvalidStatusTransitionException::make($currentStatus, $targetStatus);
        }

        throw InvalidStatusTransitionException::make($currentStatus, $targetStatus);
    }
}
