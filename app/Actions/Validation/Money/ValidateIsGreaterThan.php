<?php

namespace App\Actions\Validation\Money;

use App\ValueObjects\Money;
use Illuminate\Validation\ValidationException;

class ValidateIsGreaterThan
{
    public function execute(Money $greater, Money $minor, string $firstField, string $secondField): void
    {
        if (! $greater->isGreaterThan($minor)) {
            throw ValidationException::withMessages([
                $firstField => __('validation.must_be_greater_than', [
                    'first' => __("validation.attributes.$firstField"),
                    'second' => __("validation.attributes.$secondField"),
                ]),
            ]);
        }
    }
}
