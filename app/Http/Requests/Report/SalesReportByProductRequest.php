<?php

namespace App\Http\Requests\Report;

use App\Enum\Status\OrderStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalesReportByProductRequest extends FormRequest
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
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'date_field' => ['nullable', 'string', 'in:confirmed_at,created_at'],
            'status' => ['nullable', 'string', Rule::enum(OrderStatus::class)],
            'product_id' => ['nullable', 'uuid', 'exists:products,id'],
            'seller_id' => ['nullable', 'uuid', 'exists:sellers,id'],
            'category_id' => ['nullable', 'uuid', 'exists:categories,id'],
            'brand_id' => ['nullable', 'uuid', 'exists:brands,id'],
            'order_by' => ['nullable', 'string', 'in:total_amount,quantity,orders_count'],
            'order_direction' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
