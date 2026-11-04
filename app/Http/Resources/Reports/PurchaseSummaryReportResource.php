<?php

namespace App\Http\Resources\Reports;

use App\Enum\Status\OrderStatus;
use App\Enum\Status\PaymentStatus;
use App\Models\PurchaseOrder;
use App\ValueObjects\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @mixin PurchaseOrder
 */
class PurchaseSummaryReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var LengthAwarePaginator $paginator */
        $paginator = $this->resource['orders'];
        $totals = $this->resource['totals'];

        return [
            'totals' => [
                'orders_count' => (int) $totals['orders_count'],
                'total_amount' => (string) $totals['total_amount'],
                'total_amount_formatted' => (string) $totals['total_amount_formatted'],
                'average_ticket' => (string) $totals['average_ticket'],
                'average_ticket_formatted' => (string) $totals['average_ticket_formatted'],
                'total_items_quantity' => (string) $totals['total_items_quantity'],
                'by_status' => collect($totals['by_status'])->map(function (array $s) {
                    return [
                        'count' => (int) $s['count'],
                        'total' => (string) $s['total'],
                        'total_formatted' => (string) $s['total_formatted'],
                    ];
                })->all(),
                'by_payment_status' => collect($totals['by_payment_status'])->map(function (array $s) {
                    return [
                        'count' => (int) $s['count'],
                        'total' => (string) $s['total'],
                        'total_formatted' => (string) $s['total_formatted'],
                    ];
                })->all(),
            ],
            'data' => collect($paginator->items())->map(function (PurchaseOrder $order) {
                return [
                    'id' => (string) $order->id,
                    'number' => (int) $order->number,
                    'status' => $order->status instanceof OrderStatus ? $order->status->value : (string) $order->status,
                    'payment_status' => $order->payment_status instanceof PaymentStatus ? $order->payment_status->value : (string) $order->payment_status,
                    'total' => $order->total instanceof Money ? $order->total->toStorageString() : Money::fromDecimal($order->total ?? 0)->toStorageString(),
                    'total_formatted' => $order->total instanceof Money ? $order->total->formatted() : Money::fromDecimal($order->total ?? 0)->formatted(),
                    'items_count' => (int) ($order->items_count ?? 0),
                    'confirmed_at' => $order->confirmed_at?->toIso8601String(),
                    'created_at' => $order->created_at?->toIso8601String(),
                    'supplier' => $order->supplier ? [
                        'id' => (string) $order->supplier->id,
                        'number' => (int) $order->supplier->number,
                        'trade_name' => (string) $order->supplier->trade_name,
                        'legal_name' => (string) $order->supplier->legal_name,
                    ] : null,
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
