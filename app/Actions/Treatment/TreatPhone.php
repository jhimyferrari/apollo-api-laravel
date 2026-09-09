<?php

namespace App\Actions\Treatment;

use App\Actions\Validation\ValidateFieldIsNotNull;

class TreatPhone
{
    public function __construct(
        private ValidateFieldIsNotNull $validateFieldIsNotNull
    ) {}

    public function execute($value, bool $mustBeNotNull = false): ?string
    {
        if ($mustBeNotNull) {
            $this->validateFieldIsNotNull->execute($value, 'phone');
        }
        if ($value === null) {
            return $value;
        }
        $value = trim($value);

        return $value;
    }
}
