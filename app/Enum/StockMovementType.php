<?php

namespace App\Enum;

use App\Traits\Enum\HasEnumValues;

enum StockMovementType: string
{
    use HasEnumValues;
    case In = 'in';
    case Out = 'out';

}
