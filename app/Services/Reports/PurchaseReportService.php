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
        $status = $filters['status'] ?? OrderStatus::Confirmed->value;
        $defaultDateField = ($status === OrderStatus::Confirmed->value) ? 'confirmed_at' : 'created_at';
        $dateField = $filters['date_field'] ?? $defaultDateField;

        $query = PurchaseOrder::query();

        $this->applyDateFilters($query, $filters, $dateField);

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('purchase_orders.status', $filters['status']);
        } elseif (empty($filters['status'])) {
            $query->where('purchase_orders.status', OrderStatus::Confirmed->value);
        }

        if (! empty($filters['payment_status'])) {
            $query->where('purchase_orders.payment_status', $filters['payment_status']);
        }

        if (! empty($filters['supplier_id'])) {
            $query->where('purchase_orders.supplier_id', $filters['supplier_id']);
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

        // Status breakdown
        $statusBreakdownQuery = PurchaseOrder::query();
        $this->applyDateFilters($statusBreakdownQuery, $filters, $dateField);
        if (! empty($filters['supplier_id'])) {
            $statusBreakdownQuery->where('purchase_orders.supplier_id', $filters['supplier_id']);
        }
        $byStatus = $statusBreakdownQuery
            ->selectRaw('status, COUNT(*) as count, SUM(total) as total')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
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

        // Payment status breakdown
        $byPaymentStatus = (clone $query)
            ->selectRaw('payment_status, COUNT(*) as count, SUM(total) as total')
            ->groupBy('payment_status')
            ->get()
            ->mapWithKeys(function ($item) {
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

        // Paginated orders
        $perPage = (int) ($filters['per_page'] ?? 15);
        $orders = (clone $query)
            ->with('supplier')
            ->withCount('items')
            ->orderBy("purchase_orders.{$dateField}", 'desc')
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
            ],
            'orders' => $orders,
        ];
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
