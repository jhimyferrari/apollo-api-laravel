<?php

namespace App\Http\Resources\Reports;

use App\Models\Seller;
use App\ValueObjects\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @mixin Seller
 */
class SalesBySellerReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var LengthAwarePaginator<int, object> $paginator */
        $paginator = $this->resource['sellers'];

        /**
         * @var array{
         *     active_sellers_count: int,
         *     orders_count: int,
         *     total_amount: Money
         * } $totals
         */
        $totals = $this->resource['totals'];
        $globalTotalMoney = $this->resolveMoney($totals['total_amount'] ?? null);
        $globalTotal = (float) $globalTotalMoney->toStorageString();

        return [
            'totals' => [
                'active_sellers_count' => (int) $totals['active_sellers_count'],
                'orders_count' => (int) $totals['orders_count'],
                'total_amount' => $globalTotalMoney,
            ],
            'data' => collect($paginator->items())->map(function (object $item) use ($globalTotal): array {
                $ordersCount = (int) $item->orders_count;
                $totalAmount = $this->resolveMoney($item->total_amount ?? null);
                $averageTicket = $ordersCount > 0 ? $totalAmount->divide($ordersCount) : Money::zero();
                $percentage = $globalTotal > 0 ? round(((float) $totalAmount->toStorageString() / $globalTotal) * 100, 2) : 0.0;

                return [
                    'seller_id' => (string) $item->seller_id,
                    'seller_number' => (int) $item->seller_number,
                    'trade_name' => (string) $item->seller_trade_name,
                    'legal_name' => (string) $item->seller_legal_name,
                    'document' => (string) $item->seller_document,
                    'orders_count' => $ordersCount,
                    'total_amount' => $totalAmount,
                    'average_ticket' => $averageTicket,
                    'percentage_of_total' => $percentage,
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
