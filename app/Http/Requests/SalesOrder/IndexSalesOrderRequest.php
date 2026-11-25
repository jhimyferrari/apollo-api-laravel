<?php

namespace App\Http\Requests\SalesOrder;

use App\Enum\Status\OrderStatus;
use App\Enum\Status\PaymentStatus;
use App\Http\Requests\IndexQueryRequest;
use Illuminate\Validation\Rule;

class IndexSalesOrderRequest extends IndexQueryRequest
{
    protected function sortableColumns(): array
    {
        return ['number', 'client', 'seller', 'total', 'status', 'payment_status', 'created_at'];
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

