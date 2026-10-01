<?php

namespace App\Enum\Status;

use App\Traits\Enum\HasEnumValues;

enum PaymentStatus: string
{
    use HasEnumValues;
    case Pending = 'pending';
    case Paid = 'paid';

}
