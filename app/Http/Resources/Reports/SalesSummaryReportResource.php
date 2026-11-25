<?php

namespace App\Http\Resources\Reports;

use App\Enum\Status\OrderStatus;
use App\Enum\Status\PaymentStatus;
use App\Models\SalesOrder;
use App\ValueObjects\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @mixin SalesOrder
 */
class SalesSummaryReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var LengthAwarePaginator<int, SalesOrder> $paginator */
        $paginator = $this->resource['orders'];

        /**
         * @var array{
         *     orders_count: int,
         *     total_amount: Money,
         *     average_ticket: Money,
         *     total_items_quantity: string,
         *     by_status: array<string, array{count: int, total: Money}>,
         *     by_payment_status: array<string, array{count: int, total: Money}>,
         *     pending_refund: array{count: int, total: Money}
         * } $totals
         */
        $totals = $this->resource['totals'];

        return [
            'totals' => [
                'orders_count' => (int) $totals['orders_count'],
                'total_amount' => $this->resolveMoney($totals['total_amount']),
                'average_ticket' => $this->resolveMoney($totals['average_ticket']),
                'total_items_quantity' => (string) $totals['total_items_quantity'],
                'by_status' => collect($totals['by_status'])->map(function (array $s): array {
                    return [
                        'count' => (int) $s['count'],
                        'total' => $this->resolveMoney($s['total']),
                    ];
                })->all(),
                'by_payment_status' => collect($totals['by_payment_status'])->map(function (array $s): array {
                    return [
                        'count' => (int) $s['count'],
                        'total' => $this->resolveMoney($s['total']),
                    ];
                })->all(),
                'pending_refund' => [
                    'count' => (int) $totals['pending_refund']['count'],
                    'total' => $this->resolveMoney($totals['pending_refund']['total']),
                ],
            ],
            'data' => collect($paginator->items())->map(function (SalesOrder $order): array {
                return [
                    'id' => (string) $order->id,
                    'number' => (int) $order->number,
                    'status' => $order->status instanceof OrderStatus ? $order->status->value : (string) $order->status,
                    'payment_status' => $order->payment_status instanceof PaymentStatus ? $order->payment_status->value : (string) $order->payment_status,
                    'total' => $this->resolveMoney($order->total),
                    'items_count' => (int) ($order->items_count ?? 0),
                    'confirmed_at' => $order->confirmed_at?->toIso8601String(),
                    'paid_at' => $order->paid_at?->toIso8601String(),
                    'refunded_at' => $order->refunded_at?->toIso8601String(),
                    'cancelled_at' => $order->cancelled_at?->toIso8601String(),
                    'created_at' => $order->created_at?->toIso8601String(),
                    'client' => $order->client ? [
                        'id' => (string) $order->client->id,
                        'number' => (int) $order->client->number,
                        'trade_name' => (string) $order->client->trade_name,
                        'legal_name' => (string) $order->client->legal_name,
                    ] : null,
                    'seller' => $order->seller ? [
                        'id' => (string) $order->seller->id,
                        'number' => (int) $order->seller->number,
                        'trade_name' => (string) $order->seller->trade_name,
                        'legal_name' => (string) $order->seller->legal_name,
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

    private function resolveMoney(mixed $value): Money
    {
        return $value instanceof Money
            ? $value
            : Money::fromStorage($value !== null ? (string) $value : null);
    }
}
