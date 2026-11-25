<?php

namespace App\Http\Requests\Supplier;

use App\Enum\Status\SupplierStatus;
use App\Http\Requests\IndexQueryRequest;
use Illuminate\Validation\Rule;

class IndexSupplierRequest extends IndexQueryRequest
{
    protected function sortableColumns(): array
    {
        return ['number', 'legal_name', 'trade_name', 'document', 'status', 'created_at'];
    }

    public function rules(): array
    {
        return [
            ...$this->baseRules(),
            'status' => ['nullable', Rule::enum(SupplierStatus::class)],
        ];
    }
}
