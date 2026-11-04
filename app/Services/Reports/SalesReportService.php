<?php

namespace App\Services\Reports;

use App\Enum\Status\OrderStatus;
use App\Enum\Status\PaymentStatus;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\ValueObjects\Money;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SalesReportService
{
    /**
     * Get summary sales report with aggregated metrics and paginated orders.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function summary(array $filters): array
    {
        $status = $filters['status'] ?? OrderStatus::Confirmed->value;
        $defaultDateField = ($status === OrderStatus::Confirmed->value) ? 'confirmed_at' : 'created_at';
        $dateField = $filters['date_field'] ?? $defaultDateField;

        $query = SalesOrder::query();

        $this->applyDateFilters($query, $filters, $dateField);

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('sales_orders.status', $filters['status']);
        } elseif (empty($filters['status'])) {
            $query->where('sales_orders.status', OrderStatus::Confirmed->value);
        }

        if (! empty($filters['payment_status'])) {
            $query->where('sales_orders.payment_status', $filters['payment_status']);
        }

        if (! empty($filters['client_id'])) {
            $query->where('sales_orders.client_id', $filters['client_id']);
        }

        if (! empty($filters['seller_id'])) {
            $query->where('sales_orders.seller_id', $filters['seller_id']);
        }

        // Totals calculation
        $ordersCount = (int) (clone $query)->count();
        $totalAmountFloat = (float) ((clone $query)->sum('total') ?? 0);
        $totalAmountMoney = Money::fromDecimal($totalAmountFloat);
        $avgTicketFloat = $ordersCount > 0 ? $totalAmountFloat / $ordersCount : 0;
        $avgTicketMoney = Money::fromDecimal($avgTicketFloat);

        // Total items quantity
        $orderIds = (clone $query)->select('sales_orders.id');
        $totalItemsQuantity = (float) (SalesOrderItem::whereIn('sales_order_id', $orderIds)->sum('quantity') ?? 0);

        // Status breakdown (applied with same filters except status)
        $statusBreakdownQuery = SalesOrder::query();
        $this->applyDateFilters($statusBreakdownQuery, $filters, $dateField);
        if (! empty($filters['seller_id'])) {
            $statusBreakdownQuery->where('sales_orders.seller_id', $filters['seller_id']);
        }
        if (! empty($filters['client_id'])) {
            $statusBreakdownQuery->where('sales_orders.client_id', $filters['client_id']);
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
            ->with(['client', 'seller'])
            ->withCount('items')
            ->orderBy("sales_orders.{$dateField}", 'desc')
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
     * Get sales report grouped by product.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getByProduct(array $filters): array
    {
        $status = $filters['status'] ?? OrderStatus::Confirmed->value;
        $defaultDateField = ($status === OrderStatus::Confirmed->value) ? 'confirmed_at' : 'created_at';
        $dateField = $filters['date_field'] ?? $defaultDateField;

        $query = SalesOrderItem::query()
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
            ->join('products', 'products.id', '=', 'sales_order_items.product_id')
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->whereNull('sales_orders.deleted_at')
            ->whereNull('sales_order_items.deleted_at');

        $this->applyDateFilters($query, $filters, "sales_orders.{$dateField}");

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('sales_orders.status', $filters['status']);
        } elseif (empty($filters['status'])) {
            $query->where('sales_orders.status', OrderStatus::Confirmed->value);
        }

        if (! empty($filters['seller_id'])) {
            $query->where('sales_orders.seller_id', $filters['seller_id']);
        }

        if (! empty($filters['product_id'])) {
            $query->where('products.id', $filters['product_id']);
        }

        if (! empty($filters['brand_id'])) {
            $query->where('products.brand_id', $filters['brand_id']);
        }

        if (! empty($filters['category_id'])) {
            $query->whereExists(function ($sub) use ($filters) {
                $sub->selectRaw(1)
                    ->from('product_categories')
                    ->whereColumn('product_categories.product_id', 'products.id')
                    ->where('product_categories.category_id', $filters['category_id']);
            });
        }

        // Global Totals
        $totalsQuery = clone $query;
        $distinctProductsCount = (int) (clone $totalsQuery)->distinct('products.id')->count('products.id');
        $totalQuantity = (float) ((clone $totalsQuery)->sum('sales_order_items.quantity') ?? 0);
        $totalAmountFloat = (float) ((clone $totalsQuery)->sum('sales_order_items.subtotal') ?? 0);
        $totalAmountMoney = Money::fromDecimal($totalAmountFloat);

        // Sorting
        $orderBy = match ($filters['order_by'] ?? 'total_amount') {
            'quantity' => 'quantity_sold',
            'orders_count' => 'orders_count',
            default => 'total_amount',
        };
        $orderDirection = strtolower($filters['order_direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $perPage = (int) ($filters['per_page'] ?? 15);

        $products = (clone $query)
            ->select([
                'products.id as product_id',
                'products.number as product_number',
                'products.name as product_name',
                'products.unit as product_unit',
                'brands.id as brand_id',
                'brands.name as brand_name',
                DB::raw('SUM(sales_order_items.quantity) as quantity_sold'),
                DB::raw('SUM(sales_order_items.subtotal) as total_amount'),
                DB::raw('COUNT(DISTINCT sales_orders.id) as orders_count'),
            ])
            ->groupBy(
                'products.id',
                'products.number',
                'products.name',
                'products.unit',
                'brands.id',
                'brands.name'
            )
            ->orderBy($orderBy, $orderDirection)
            ->paginate($perPage);

        return [
            'totals' => [
                'distinct_products_count' => $distinctProductsCount,
                'total_quantity' => (string) $totalQuantity,
                'total_amount' => $totalAmountMoney->toStorageString(),
                'total_amount_formatted' => $totalAmountMoney->formatted(),
            ],
            'products' => $products,
        ];
    }

    /**
     * Get sales report grouped by seller.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getBySeller(array $filters): array
    {
        $status = $filters['status'] ?? OrderStatus::Confirmed->value;
        $defaultDateField = ($status === OrderStatus::Confirmed->value) ? 'confirmed_at' : 'created_at';
        $dateField = $filters['date_field'] ?? $defaultDateField;

        $query = SalesOrder::query()
            ->join('sellers', 'sellers.id', '=', 'sales_orders.seller_id')
            ->whereNull('sales_orders.deleted_at');

        $this->applyDateFilters($query, $filters, "sales_orders.{$dateField}");

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('sales_orders.status', $filters['status']);
        } elseif (empty($filters['status'])) {
            $query->where('sales_orders.status', OrderStatus::Confirmed->value);
        }

        if (! empty($filters['seller_id'])) {
            $query->where('sellers.id', $filters['seller_id']);
        }

        // Global Totals
        $globalTotals = clone $query;
        $activeSellersCount = (int) (clone $globalTotals)->distinct('sellers.id')->count('sellers.id');
        $totalOrdersCount = (int) ((clone $globalTotals)->count('sales_orders.id') ?? 0);
        $totalAmountFloat = (float) ((clone $globalTotals)->sum('sales_orders.total') ?? 0);
        $totalAmountMoney = Money::fromDecimal($totalAmountFloat);

        // Sorting
        $orderBy = match ($filters['order_by'] ?? 'total_amount') {
            'orders_count' => 'orders_count',
            default => 'total_amount',
        };
        $orderDirection = strtolower($filters['order_direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $perPage = (int) ($filters['per_page'] ?? 15);

        $sellers = (clone $query)
            ->select([
                'sellers.id as seller_id',
                'sellers.number as seller_number',
                'sellers.trade_name as seller_trade_name',
                'sellers.legal_name as seller_legal_name',
                'sellers.document as seller_document',
                DB::raw('COUNT(sales_orders.id) as orders_count'),
                DB::raw('SUM(sales_orders.total) as total_amount'),
            ])
            ->groupBy(
                'sellers.id',
                'sellers.number',
                'sellers.trade_name',
                'sellers.legal_name',
                'sellers.document'
            )
            ->orderBy($orderBy, $orderDirection)
            ->paginate($perPage);

        return [
            'totals' => [
                'active_sellers_count' => $activeSellersCount,
                'orders_count' => $totalOrdersCount,
                'total_amount' => $totalAmountMoney->toStorageString(),
                'total_amount_formatted' => $totalAmountMoney->formatted(),
            ],
            'sellers' => $sellers,
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
