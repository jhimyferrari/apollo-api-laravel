<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'status' => $this->status,
            'description' => $this->description,
            'unit' => $this->unit,
            'ncm' => $this->ncm,
            'ean' => $this->ean,
            'cost_price' => $this->cost_price,
            'sale_price' => $this->sale_price,
            'stock_quantity' => $this->sale_price,
            'brand' => new BrandResource($this->whenLoaded('brand')),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
