<?php

namespace App\Interfaces\Models;

interface HasStatus
{
    public function statusEnumClass(): string;

    public function isActive(): bool;
}
