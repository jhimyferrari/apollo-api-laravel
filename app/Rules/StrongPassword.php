<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    public function __construct(private int $min = 8) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail(__('validation.string', ['attribute' => 'password']));

            return;
        }

        if (mb_strlen($value) < $this->min) {
            $fail(__('validation.min.string', ['attribute' => 'password', 'min' => $this->min]));
        }

        if (! preg_match('/\p{L}/u', $value)) {
            $fail(__('validation.password.letters', ['attribute' => 'password']));
        }

        if (! preg_match('/\p{Ll}/u', $value) || ! preg_match('/\p{Lu}/u', $value)) {
            $fail(__('validation.password.mixed', ['attribute' => 'password']));
        }
        if (! preg_match('/\d/', $value)) {
            $fail(__('validation.password.numbers', ['attribute' => 'password']));
        }
        if (! preg_match('/[\p{P}\p{S}]/u', $value)) {
            $fail(__('validation.password.symbols', ['attribute' => 'password']));
        }
    }
}
