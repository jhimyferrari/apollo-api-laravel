<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class CpfAndCnpj implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = preg_replace('/[^0-9]/', '', $value);

        match (\strlen($value)) {
            11 => $this->validateCpf($value) ?: $fail('Invalid document.'),
            14 => $this->validateCnpj($value) ?: $fail('Invalid document.'),
            default => $fail('Invalid document.'),
        };

    }

    private function validateCpf($cpf)
    {
        if (str_repeat($cpf[0], 11) === $cpf) {
            return false;
        }
        for ($t = 9; $t < 11; $t++) {
            $sum = 0;
            for ($i = 0; $i < $t; $i++) {
                $sum += $cpf[$i] * ($t + 1 - $i);
            }
            $digit = ((10 * $sum) % 11) % 10;
            if ($cpf[$t] != $digit) {
                return false;
            }
        }

        return true;
    }

    private function validateCnpj(string $cnpj): bool
    {
        if (str_repeat($cnpj[0], 14) === $cnpj) {
            return false;
        }

        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        foreach ([$weights1, $weights2] as $i => $weights) {
            $sum = 0;
            foreach ($weights as $j => $w) {
                $sum += $cnpj[$j] * $w;
            }
            $digit = $sum % 11 < 2 ? 0 : 11 - ($sum % 11);
            if ($cnpj[\count($weights)] != $digit) {
                return false;
            }
        }

        return true;
    }
}
