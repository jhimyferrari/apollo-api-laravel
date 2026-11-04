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
        /** @var LengthAwarePaginator $paginator */
        $paginator = $this->resource['products'];
        $totals = $this->resource['totals'];

        return [
            'totals' => [
                'distinct_products_count' => (int) $totals['distinct_products_count'],
                'total_quantity' => (string) $totals['total_quantity'],
                'total_amount' => (string) $totals['total_amount'],
                'total_amount_formatted' => (string) $totals['total_amount_formatted'],
            ],
            'data' => collect($paginator->items())->map(function (object $item) {
                $qty = (float) $item->quantity_sold;
                $totalAmt = (float) ($item->total_amount instanceof Money ? $item->total_amount->toStorageString() : ($item->total_amount ?? 0));
                $avgPrice = $qty > 0 ? $totalAmt / $qty : 0;

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
                    'total_amount' => Money::fromDecimal($totalAmt)->toStorageString(),
                    'total_amount_formatted' => Money::fromDecimal($totalAmt)->formatted(),
                    'average_unit_price' => Money::fromDecimal($avgPrice)->toStorageString(),
                    'average_unit_price_formatted' => Money::fromDecimal($avgPrice)->formatted(),
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
}
