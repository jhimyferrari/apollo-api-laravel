<?php

namespace App\Enum\Status;

use App\Traits\Enum\HasEnumValues;

enum SellerStatus: string
{
    use HasEnumValues;
    case Active = 'active';
    case Inactive = 'inactive';
}
