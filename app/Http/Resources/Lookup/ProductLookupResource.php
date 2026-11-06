<?php

namespace App\Http\Resources\Lookup;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductLookupResource extends JsonResource
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
            'name' => $this->name,
            'unit' => $this->unit,
            'sale_price' => $this->sale_price,
            'cost_price' => $this->cost_price,
            'stock_quantity' => $this->stock_quantity,
            'status' => $this->status,
        ];
    }
}
