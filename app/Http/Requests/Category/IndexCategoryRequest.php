<?php

namespace App\Http\Requests\Category;

use App\Http\Requests\IndexQueryRequest;

class IndexCategoryRequest extends IndexQueryRequest
{
    protected function sortableColumns(): array
    {
        return ['number', 'name', 'created_at'];
    }
}
