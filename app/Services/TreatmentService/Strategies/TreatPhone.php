<?php

namespace App\Services\TreatmentService\Strategies;

use Illuminate\Validation\ValidationException;

class TreatPhone implements TreatmentStrategy
{
    public function handle(mixed $value, string $field): string
    {
        if (! \is_string($value)) {
            throw ValidationException::withMessages([$field => __('validation.string', ['attribute' => __("validation.attributes.$field")])]);
        }

        $value = trim($value);
        $digits = preg_replace('/\D/', '', $value) ?? '';

        if (! preg_match('/^[0-9+\-().\sxX]+$/', $value) || \strlen($digits) < 8) {
            throw ValidationException::withMessages([$field => __('validation.regex', ['attribute' => __("validation.attributes.$field")])]);
        }

        return $value;
    }
}
