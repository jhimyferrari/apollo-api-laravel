<?php

namespace App\Http\Requests\User;

use App\Http\Requests\IndexQueryRequest;
use Illuminate\Validation\Rule;

class IndexUserRequest extends IndexQueryRequest
{
    protected function sortableColumns(): array
    {
        return ['name', 'email', 'permissions', 'created_at'];
    }

    public function rules(): array
    {
        return [
            ...$this->baseRules(),
            'profile' => ['nullable', 'string', Rule::in(['admin', 'operator'])],
        ];
    }
}
