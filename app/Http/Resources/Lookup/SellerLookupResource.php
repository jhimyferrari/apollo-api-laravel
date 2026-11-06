<?php

namespace App\Http\Resources\Lookup;

use App\Models\Seller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Seller
 */
class SellerLookupResource extends JsonResource
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
            'name' => $this->trade_name ?: $this->legal_name,
            'status' => $this->status,
        ];
    }
}
