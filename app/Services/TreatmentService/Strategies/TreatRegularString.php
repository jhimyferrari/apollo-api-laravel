<?php

namespace App\Services\TreatmentService\Strategies;

use Illuminate\Validation\ValidationException;

class TreatRegularString implements TreatmentStrategy
{
    public function handle(mixed $value, string $field): string
    {
        if (! \is_string($value)) {
            throw ValidationException::withMessages([$field => __('validation.string', ['attribute' => __("validation.attribute.$field")])]);
        }
        $value = trim($value);

        return $value;
    }
}
