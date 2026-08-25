<?php

namespace App\Enum\Status;

use App\Traits\Enum\HasEnumValues;

enum ClientStatus: string
{
    use HasEnumValues;
    case Active = 'active';
    case Inactive = 'inactive';
}
