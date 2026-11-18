<?php

namespace App\Http\Requests\PurchaseOrder;

use App\Http\Requests\Order\BaseUpdateOrderRequest;
use App\Models\PurchaseOrder;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdatePurchaseOrderRequest extends BaseUpdateOrderRequest
{
    protected function resolveOrder(): ?PurchaseOrder
    {
        $order = $this->route('purchaseOrder');

        return $order instanceof PurchaseOrder ? $order : null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'supplier_id' => ['sometimes', 'uuid'],

            'items' => ['sometimes', 'array'],

            'items.to_add' => ['sometimes', 'array'],
            'items.to_add.*.product_id' => ['required_with:items.to_add', 'uuid', 'distinct'],
            'items.to_add.*.number' => ['required_with:items.to_add', 'integer', 'min:1', 'distinct'],
            'items.to_add.*.quantity' => ['required_with:items.to_add', 'numeric', 'gt:0', 'decimal:0,3', 'max:9999999.999'],

            'items.to_update' => ['sometimes', 'array'],
            'items.to_update.*.id' => ['required_with:items.to_update', 'uuid', 'distinct'],
            'items.to_update.*.product_id' => ['sometimes', 'uuid', 'distinct'],
            'items.to_update.*.number' => ['sometimes', 'integer', 'min:1', 'distinct'],
            'items.to_update.*.quantity' => ['sometimes', 'numeric', 'gt:0', 'decimal:0,3', 'max:9999999.999'],

            'items.to_remove' => ['sometimes', 'array'],
            'items.to_remove.*' => ['required_with:items.to_remove', 'uuid', 'distinct'],
        ];
    }
}
