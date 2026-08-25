<?php

namespace App\Actions\Validation;

use App\Exceptions\InvalidFieldException;

class ValidateEAN
{
    /**
     * @throws InvalidFieldException
     */
    public function execute(string $value): string
    {
        $value = trim($value);
        $isValid = match (\strlen($value)) {
            13 => $this->validateEan13($value),
            8 => $this->validateEan8($value),
            default => false
        };
        if (! $isValid) {
            throw new InvalidFieldException("The EAN code `$value` it`s not valid ");
        }

        return $value;
    }

    private function validateEan8(string $ean): string
    {
        $digits = array_map('intval', str_split($ean));
        $checkDigit = array_pop($digits);

        $sum = 0;
        foreach ($digits as $index => $digit) {
            $sum += $digit * ($index % 2 === 0 ? 3 : 1);
        }

        if ((10 - ($sum % 10)) % 10 === $checkDigit) {
            return (10 - ($sum % 10)) % 10 === $checkDigit;
        }
    }

    private function validateEan13(string $ean): bool
    {
        $digits = array_map('intval', str_split($ean));
        $checkDigit = array_pop($digits);

        $sum = 0;
        foreach ($digits as $index => $digit) {
            $sum += $digit * ($index % 2 === 0 ? 1 : 3);
        }

        return (10 - ($sum % 10)) % 10 === $checkDigit;
    }
}
