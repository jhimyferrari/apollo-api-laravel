<?php

namespace App\Services;

use App\Models\Client;

class ClientService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new Client);
    }
}
