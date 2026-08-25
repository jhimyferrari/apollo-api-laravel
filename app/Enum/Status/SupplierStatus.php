<?php

namespace App\Enum\Status;

use App\Traits\Enum\HasEnumValues;

enum SupplierStatus: string
{
    use HasEnumValues;
    case Active = 'active';
    case Inactive = 'inactive';
}
