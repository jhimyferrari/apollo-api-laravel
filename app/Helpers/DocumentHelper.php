<?php

namespace App\Helpers;

class DocumentHelper
{
    /**
     * Return string just with numerical characters.
     */
    public static function formatCpfAndCnpj(string $rawValue): string
    {
        return preg_replace('/[^0-9]/', '', $rawValue);

    }
}
