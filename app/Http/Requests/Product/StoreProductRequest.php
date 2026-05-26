<?php

namespace App\Http\Requests\Product;

use App\Enum\ProductUnit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:45'],
            'description' => ['nullable', 'string'],
            'unit' => ['required', 'string', Rule::in(ProductUnit::values())],
            'ncm' => ['nullable', 'string', 'size:8'],
            'ean' => ['nullable', 'string', 'size:13'],
            'cost_price' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'sale_price' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'brand_id' => ['nullable', 'string'],
            'categories' => ['nullable', 'array'],
            'categories.*.id' => ['required_with', 'string'],
        ];
    }
}
