<?php

namespace App\Enum\Status;

use App\Traits\Enum\HasEnumValues;

enum ProductStatus: string
{
    use HasEnumValues;
    case Active = 'active';
    case Inactive = 'inactive';
    case Discontinued = 'discontinued';
}
