<?php

namespace App\Rules;

use App\Helpers\DocumentHelper;
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
        $value = DocumentHelper::remove_pontuation($value);

        match (\strlen($value)) {
            11 => $this->validateCpf($value) ?: $fail(__('validation.document', ['attribute' => $value])),
            14 => $this->validateCnpj($value) ?: $fail(__('validation.document', ['attribute' => $value])),
            default => $fail(__('validation.document', ['attribute' => $value])),
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

        if (! preg_match('/^[A-Z0-9]{12}[0-9]{2}$/', $cnpj)) {
            return false;
        }

        if (str_repeat($cnpj[0], 14) === $cnpj) {
            return false;
        }

        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        foreach ([$weights1, $weights2] as $weights) {
            $sum = 0;
            foreach ($weights as $j => $w) {
                $sum += (\ord($cnpj[$j]) - 48) * $w;
            }

            $remainder = $sum % 11;
            $digit = $remainder < 2 ? 0 : 11 - $remainder;

            if ((int) $cnpj[\count($weights)] !== $digit) {
                return false;
            }
        }

        return true;
    }
}
