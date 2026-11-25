<?php

namespace App\Http\Requests\Client;

use App\Enum\Status\ClientStatus;
use App\Http\Requests\IndexQueryRequest;
use Illuminate\Validation\Rule;

class IndexClientRequest extends IndexQueryRequest
{
    protected function sortableColumns(): array
    {
        return ['number', 'legal_name', 'trade_name', 'document', 'status', 'created_at'];
    }

    public function rules(): array
    {
        return [
            ...$this->baseRules(),
            'status' => ['nullable', Rule::enum(ClientStatus::class)],
        ];
    }
}

