<?php

namespace App\Interfaces\Models;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

interface MovesStock
{
    public function stockMovements(): MorphMany;

    public function stockMovementItems(): Collection;
}
