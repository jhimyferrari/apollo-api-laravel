<?php

namespace App\Services\TreatmentService\Strategies;

use Illuminate\Validation\ValidationException;

class TreatEmail implements TreatmentStrategy
{
    public function handle(mixed $value, string $field): string
    {

        if (! \is_string($value)) {
            throw ValidationException::withMessages([$field => __('validation.email', ['attribute' => __('validation.attributes.email')])]);
        }
        $value = trim($value);

        return $value;

    }
}
