<?php

namespace App\Helpers;

class DocumentHelper
{
    /**
     * Return string just with alfanumerics characters in uppercase.
     */
    public static function remove_pontuation(string $rawValue): string
    {

        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $rawValue));

    }
}
