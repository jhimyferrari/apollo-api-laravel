<?php

namespace App\Http\Resources\Reports;

use App\Models\Product;
use App\ValueObjects\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @mixin Product
 */
class SalesByProductReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var LengthAwarePaginator<int, object> $paginator */
        $paginator = $this->resource['products'];

        /**
         * @var array{
         *     distinct_products_count: int,
         *     total_quantity: string,
         *     total_amount: Money
         * } $totals
         */
        $totals = $this->resource['totals'];

        return [
            'totals' => [
                'distinct_products_count' => (int) $totals['distinct_products_count'],
                'total_quantity' => (string) $totals['total_quantity'],
                'total_amount' => $this->resolveMoney($totals['total_amount']),
            ],
            'data' => collect($paginator->items())->map(function (object $item): array {
                $qty = (float) $item->quantity_sold;
                $totalAmount = $this->resolveMoney($item->total_amount ?? null);
                $averageUnitPrice = $qty > 0 ? $totalAmount->divide((string) $item->quantity_sold) : Money::zero();

                return [
                    'product_id' => (string) $item->product_id,
                    'product_number' => (int) $item->product_number,
                    'product_name' => (string) $item->product_name,
                    'product_unit' => (string) $item->product_unit,
                    'brand' => $item->brand_id ? [
                        'id' => (string) $item->brand_id,
                        'name' => (string) $item->brand_name,
                    ] : null,
                    'quantity_sold' => (string) $qty,
                    'total_amount' => $totalAmount,
                    'average_unit_price' => $averageUnitPrice,
                    'orders_count' => (int) $item->orders_count,
                ];
            }),
            'meta' => [
                'current_page' => (int) $paginator->currentPage(),
                'from' => $paginator->firstItem() ? (int) $paginator->firstItem() : null,
                'last_page' => (int) $paginator->lastPage(),
                'per_page' => (int) $paginator->perPage(),
                'to' => $paginator->lastItem() ? (int) $paginator->lastItem() : null,
                'total' => (int) $paginator->total(),
            ],
            'links' => [
                'first' => (string) $paginator->url(1),
                'last' => (string) $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl() ? (string) $paginator->previousPageUrl() : null,
                'next' => $paginator->nextPageUrl() ? (string) $paginator->nextPageUrl() : null,
            ],
        ];
    }

    private function resolveMoney(mixed $value): Money
    {
        return $value instanceof Money
            ? $value
            : Money::fromStorage($value !== null ? (string) $value : null);
    }
}
