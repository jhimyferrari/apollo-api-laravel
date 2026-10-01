<?php

namespace App\Enum\Status;

use App\Traits\Enum\HasEnumValues;

enum OrderStatus: string
{
    use HasEnumValues;
    case Draft = 'draf';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

}
