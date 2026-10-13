<?php

namespace App\Actions\Validation;

use Illuminate\Validation\ValidationException;

class ValidateEAN
{
    /**
     * @throws ValidationException
     */
    public function execute(string $value, $field): string
    {
        if (! \is_string($value)) {
            throw ValidationException::withMessages([$field => __('validation.ean', ['attribute' => 'ean'])]);
        }
        $value = trim($value);
        $isValid = match (\strlen($value)) {
            13 => $this->validateEan13($value),
            8 => $this->validateEan8($value),
            default => false
        };
        if (! $isValid) {
            throw ValidationException::withMessages([$field => __('validation.ean', ['attribute' => 'ean'])]);
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
