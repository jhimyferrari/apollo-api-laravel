<?php

namespace App\Http\Resources;

use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PurchaseOrder
 */
class PurchaseOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'status' => $this->status->value,
            'payment_status' => $this->payment_status->value,

            'supplier_id' => $this->supplier_id,

            'total' => $this->total,

            'replaces_order_id' => $this->replaces_order_id,

            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'refunded_at' => $this->refunded_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason,

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            'items' => PurchaseOrderItemResource::collection($this->whenLoaded('items')),
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
        ];

    }
}
