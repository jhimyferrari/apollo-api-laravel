<?php

namespace App\Services\Reports;

use App\Enum\Status\OrderStatus;
use App\Enum\Status\PaymentStatus;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\ValueObjects\Money;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class PurchaseReportService
{
    /**
     * Get summary purchase report with aggregated metrics and paginated orders.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function summary(array $filters): array
    {
        $status = $this->resolveStatus($filters);
        $defaultDateField = ($status === OrderStatus::Confirmed->value) ? 'confirmed_at' : 'created_at';
        $dateField = $filters['date_field'] ?? $defaultDateField;

        $query = PurchaseOrder::query();

        $this->applyDateFilters($query, $filters, "purchase_orders.{$dateField}");
        $this->applySummaryPartnerFilters($query, $filters);

        if ($status !== 'all') {
            $query->where('purchase_orders.status', $status);
        }

        if (! empty($filters['payment_status'])) {
            $query->where('purchase_orders.payment_status', $filters['payment_status']);
        }

        // Totals calculation
        $ordersCount = (int) (clone $query)->count();
        $totalAmountFloat = (float) ((clone $query)->sum('total') ?? 0);
        $totalAmountMoney = Money::fromDecimal($totalAmountFloat);
        $avgTicketFloat = $ordersCount > 0 ? $totalAmountFloat / $ordersCount : 0;
        $avgTicketMoney = Money::fromDecimal($avgTicketFloat);

        // Total items quantity
        $orderIds = (clone $query)->select('purchase_orders.id');
        $totalItemsQuantity = (float) (PurchaseOrderItem::whereIn('purchase_order_id', $orderIds)->sum('quantity') ?? 0);

        // Status breakdown (applied with same filters except status)
        $statusBreakdownQuery = PurchaseOrder::query();
        $this->applyDateFilters($statusBreakdownQuery, $filters, "purchase_orders.{$dateField}");
        $this->applySummaryPartnerFilters($statusBreakdownQuery, $filters);
        if (! empty($filters['payment_status'])) {
            $statusBreakdownQuery->where('purchase_orders.payment_status', $filters['payment_status']);
        }
        $byStatus = $statusBreakdownQuery
            ->selectRaw('status, COUNT(*) as count, SUM(total) as total')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function (PurchaseOrder $item) {
                $statusVal = $item->status instanceof OrderStatus ? $item->status->value : (string) $item->status;
                $totMoney = $item->total instanceof Money ? $item->total : Money::fromDecimal($item->total ?? 0);

                return [
                    $statusVal => [
                        'count' => (int) $item->count,
                        'total' => $totMoney->toStorageString(),
                        'total_formatted' => $totMoney->formatted(),
                    ],
                ];
            })
            ->toArray();

        // Payment status breakdown (symmetric to by_status: keeps status filter, ignores payment_status)
        $paymentBreakdownQuery = PurchaseOrder::query();
        $this->applyDateFilters($paymentBreakdownQuery, $filters, "purchase_orders.{$dateField}");
        $this->applySummaryPartnerFilters($paymentBreakdownQuery, $filters);
        if ($status !== 'all') {
            $paymentBreakdownQuery->where('purchase_orders.status', $status);
        }
        $byPaymentStatus = $paymentBreakdownQuery
            ->selectRaw('payment_status, COUNT(*) as count, SUM(total) as total')
            ->groupBy('payment_status')
            ->get()
            ->mapWithKeys(function (PurchaseOrder $item) {
                $statusVal = $item->payment_status instanceof PaymentStatus ? $item->payment_status->value : (string) $item->payment_status;
                $totMoney = $item->total instanceof Money ? $item->total : Money::fromDecimal($item->total ?? 0);

                return [
                    $statusVal => [
                        'count' => (int) $item->count,
                        'total' => $totMoney->toStorageString(),
                        'total_formatted' => $totMoney->formatted(),
                    ],
                ];
            })
            ->toArray();

        // Pending refund alerts (cancelled orders that are still paid)
        $pendingRefundDateField = $filters['date_field'] ?? 'cancelled_at';
        $pendingRefundQuery = PurchaseOrder::query()
            ->where('purchase_orders.status', OrderStatus::Cancelled->value)
            ->where('purchase_orders.payment_status', PaymentStatus::Paid->value);
        $this->applyDateFilters($pendingRefundQuery, $filters, "purchase_orders.{$pendingRefundDateField}");
        $this->applySummaryPartnerFilters($pendingRefundQuery, $filters);

        $pendingRefundCount = (int) (clone $pendingRefundQuery)->count();
        $pendingRefundMoney = Money::fromDecimal((float) ((clone $pendingRefundQuery)->sum('total') ?? 0));

        // Paginated orders
        $perPage = (int) ($filters['per_page'] ?? 15);
        $orders = (clone $query)
            ->with('supplier')
            ->withCount('items')
            ->orderBy("purchase_orders.{$dateField}", 'desc')
            ->orderBy('purchase_orders.created_at', 'desc')
            ->paginate($perPage);

        return [
            'totals' => [
                'orders_count' => $ordersCount,
                'total_amount' => $totalAmountMoney->toStorageString(),
                'total_amount_formatted' => $totalAmountMoney->formatted(),
                'average_ticket' => $avgTicketMoney->toStorageString(),
                'average_ticket_formatted' => $avgTicketMoney->formatted(),
                'total_items_quantity' => (string) $totalItemsQuantity,
                'by_status' => $byStatus,
                'by_payment_status' => $byPaymentStatus,
                'pending_refund' => [
                    'count' => $pendingRefundCount,
                    'total' => $pendingRefundMoney->toStorageString(),
                    'total_formatted' => $pendingRefundMoney->formatted(),
                ],
            ],
            'orders' => $orders,
        ];
    }

    /**
     * Resolve effective order status filter, inferring Cancelled when filtering
     * by terminal payment statuses without an explicit order status.
     *
     * @param  array<string, mixed>  $filters
     */
    private function resolveStatus(array $filters): string
    {
        if (! empty($filters['status'])) {
            return $filters['status'];
        }

        $paymentStatus = $filters['payment_status'] ?? null;
        if (\in_array($paymentStatus, [PaymentStatus::Refunded->value, PaymentStatus::Cancelled->value], true)) {
            return OrderStatus::Cancelled->value;
        }

        return OrderStatus::Confirmed->value;
    }

    /**
     * Apply supplier filter to a summary query builder.
     *
     * @param  array<string, mixed>  $filters
     */
    private function applySummaryPartnerFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['supplier_id'])) {
            $query->where('purchase_orders.supplier_id', $filters['supplier_id']);
        }
    }

    /**
     * Apply date range filters to a query builder.
     */
    private function applyDateFilters(Builder $query, array $filters, string $column): void
    {
        if (! empty($filters['start_date'])) {
            $query->where($column, '>=', Carbon::parse($filters['start_date'])->startOfDay());
        }

        if (! empty($filters['end_date'])) {
            $query->where($column, '<=', Carbon::parse($filters['end_date'])->endOfDay());
        }
    }
}
