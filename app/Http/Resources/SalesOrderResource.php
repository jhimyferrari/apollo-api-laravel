<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderResource extends JsonResource
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

            'client_id' => $this->client_id,
            'seller_id' => $this->seller_id,

            'total' => $this->total->toStorageString(),

            'replaces_order_id' => $this->replaces_order_id,

            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason,

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            'items' => SalesOrderItemResource::collection($this->whenLoaded('items')),
            'client' => new ClientResource($this->whenLoaded('client')),
            'seller' => new SellerResource($this->whenLoaded('seller')),
        ];

    }
}
