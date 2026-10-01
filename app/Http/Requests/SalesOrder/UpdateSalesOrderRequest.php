<?php

namespace App\Http\Requests\SalesOrder;

use App\Enum\Status\PaymentStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSalesOrderRequest extends FormRequest
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
            'client_id' => ['sometimes', 'ulid', 'exists:clients,id'],
            'seller_id' => ['sometimes', 'ulid', 'exists:sellers,id'],
            'payment_status' => ['sometimes', Rule::enum(PaymentStatus::class)],

            'items' => ['sometimes', 'array'],

            'items.to_add' => ['sometimes', 'array'],
            'items.to_add.*.product_id' => ['required_with:items.to_add', 'uuid', 'exists:products,id'],
            'items.to_add.*.number' => ['required_with:items.to_add', 'integer', 'min:1'],
            'items.to_add.*.quantity' => ['required_with:items.to_add', 'numeric', 'gt:0'],

            'items.to_update' => ['sometimes', 'array'],
            'items.to_update.*.id' => ['sometimes', 'uuid', 'exists:sales_order_items,id'],
            'items.to_update.*.product_id' => ['sometimes', 'integer', 'min:1'],
            'items.to_update.*.number' => ['sometimes', 'integer', 'min:1'],
            'items.to_update.*.quantity' => ['sometimes', 'numeric', 'gt:0'],

            'items.to_remove' => ['sometimes', 'array'],
            'items.to_remove.*.id' => ['uuid', 'exists:sales_order_items,id'],
        ];
    }
}
