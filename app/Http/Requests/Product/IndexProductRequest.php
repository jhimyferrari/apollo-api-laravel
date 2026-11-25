<?php

namespace App\Http\Requests\Product;

use App\Enum\Status\ProductStatus;
use App\Http\Requests\IndexQueryRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class IndexProductRequest extends IndexQueryRequest
{
    protected function sortableColumns(): array
    {
        return [
            'number',
            'name',
            'unit',
            'stock_quantity',
            'cost_price',
            'sale_price',
            'status',
            'created_at',
        ];
    }

    public function rules(): array
    {
        return [
            ...$this->baseRules(),
            'status' => ['nullable', Rule::enum(ProductStatus::class)],
            'brand_id' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value !== 'none' && ! Str::isUuid((string) $value)) {
                        $fail(__('validation.uuid', ['attribute' => $attribute]));
                    }
                },
            ],
            'category_id' => ['nullable', 'uuid'],
        ];
    }
}
