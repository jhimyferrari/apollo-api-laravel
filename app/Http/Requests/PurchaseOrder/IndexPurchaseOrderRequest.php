<?php

namespace App\Http\Requests\PurchaseOrder;

use App\Enum\Status\OrderStatus;
use App\Enum\Status\PaymentStatus;
use App\Http\Requests\IndexQueryRequest;
use Illuminate\Validation\Rule;

class IndexPurchaseOrderRequest extends IndexQueryRequest
{
    protected function sortableColumns(): array
    {
        return ['number', 'supplier', 'total', 'status', 'payment_status', 'created_at'];
    }

    public function rules(): array
    {
        return [
            ...$this->baseRules(),
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            'payment_status' => ['nullable', Rule::enum(PaymentStatus::class)],
        ];
    }
}

