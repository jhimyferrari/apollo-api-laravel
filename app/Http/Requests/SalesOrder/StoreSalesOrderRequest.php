<?php

namespace App\Http\Requests\SalesOrder;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSalesOrderRequest extends FormRequest
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
            'client_id' => ['required', 'uuid', 'exists:clients,id'],
            'seller_id' => ['required', 'uuid', 'exists:sellers,id'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid', 'exists:products,id'],
            'items.*.number' => ['required', 'integer', 'min:1'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
