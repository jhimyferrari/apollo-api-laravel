<?php

namespace App\Helpers;

class DocumentHelper
{
    public static function formatCpfAndCnpj(string $rawValue): string
    {
        return preg_replace('/[^\d]/', '', $rawValue);

    }
}
