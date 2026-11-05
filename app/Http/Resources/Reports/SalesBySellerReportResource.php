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
        /** @var LengthAwarePaginator $paginator */
        $paginator = $this->resource['sellers'];
        $totals = $this->resource['totals'];
        $globalTotal = (float) ($totals['total_amount'] ?? 0);

        return [
            'totals' => [
                'active_sellers_count' => (int) $totals['active_sellers_count'],
                'orders_count' => (int) $totals['orders_count'],
                'total_amount' => (string) $totals['total_amount'],
                'total_amount_formatted' => (string) $totals['total_amount_formatted'],
            ],
            'data' => collect($paginator->items())->map(function (object $item) use ($globalTotal) {
                $ordersCount = (int) $item->orders_count;
                $totalAmt = (float) ($item->total_amount instanceof Money ? $item->total_amount->toStorageString() : ($item->total_amount ?? 0));
                $avgTicket = $ordersCount > 0 ? $totalAmt / $ordersCount : 0;
                $percentage = $globalTotal > 0 ? round(($totalAmt / $globalTotal) * 100, 2) : 0.0;

                return [
                    'seller_id' => (string) $item->seller_id,
                    'seller_number' => (int) $item->seller_number,
                    'trade_name' => (string) $item->seller_trade_name,
                    'legal_name' => (string) $item->seller_legal_name,
                    'document' => (string) $item->seller_document,
                    'orders_count' => $ordersCount,
                    'total_amount' => Money::fromDecimal($totalAmt)->toStorageString(),
                    'total_amount_formatted' => Money::fromDecimal($totalAmt)->formatted(),
                    'average_ticket' => Money::fromDecimal($avgTicket)->toStorageString(),
                    'average_ticket_formatted' => Money::fromDecimal($avgTicket)->formatted(),
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
}
