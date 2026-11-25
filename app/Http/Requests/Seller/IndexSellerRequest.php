<?php

namespace App\Http\Requests\Seller;

use App\Enum\Status\SellerStatus;
use App\Http\Requests\IndexQueryRequest;
use Illuminate\Validation\Rule;

class IndexSellerRequest extends IndexQueryRequest
{
    protected function sortableColumns(): array
    {
        return ['number', 'legal_name', 'trade_name', 'document', 'status', 'created_at'];
    }

    public function rules(): array
    {
        return [
            ...$this->baseRules(),
            'status' => ['nullable', Rule::enum(SellerStatus::class)],
        ];
    }
}
