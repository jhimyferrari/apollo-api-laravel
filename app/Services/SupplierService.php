<?php

namespace App\Services;

use App\Models\Supplier;

class SupplierService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new Supplier);
    }
}
