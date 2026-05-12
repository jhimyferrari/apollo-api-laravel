<?php

namespace App\Services;

use App\Models\Seller;

class SellerService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new Seller);
    }
}
