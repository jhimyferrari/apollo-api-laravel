<?php

namespace App\Http\Requests\Product;

use App\Enum\ProductUnit;
use App\Enum\Status\ProductStatus;
use App\Http\Requests\BaseUpdateRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends BaseUpdateRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::enum(ProductStatus::class)],
            'name' => ['sometimes', 'string', 'max:45'],
            'description' => ['nullable', 'string'],
            'unit' => ['sometimes', 'string', Rule::in(ProductUnit::allValues())],
            'ncm' => ['nullable', 'string', 'size:8'],
            'ean' => ['nullable', 'string', 'size:13'],
            'cost_price' => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
            'sale_price' => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
            'stock_quantity' => ['nullable', 'numeric', 'min:0', 'decimal:0,3'],
            'brand_id' => ['nullable', 'string'],
            'categories' => ['nullable', 'array'],
            'categories.*.id' => ['uuid'],
        ];
    }
}
