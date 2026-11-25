<?php

namespace App\Http\Requests\Brand;

use App\Http\Requests\IndexQueryRequest;

class IndexBrandRequest extends IndexQueryRequest
{
    protected function sortableColumns(): array
    {
        return ['number', 'name', 'created_at'];
    }
}

