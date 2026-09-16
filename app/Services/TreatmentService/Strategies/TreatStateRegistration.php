<?php

namespace App\Services\TreatmentService\Strategies;

use App\Actions\Validation\ValidateDuplicateField;
use App\Actions\Validation\ValidateFieldIsNotNull;
use App\Exceptions\DuplicateFieldException;
use App\Exceptions\InvalidFieldException;
use App\Helpers\DocumentHelper;
use RuntimeException;

class TreatStateRegistration implements TreatmentStrategy
{
    public function __construct(
        private ValidateDuplicateField $validateDuplicateField,
        private ValidateFieldIsNotNull $validateFieldIsNotNull
    ) {}

    /**
     * @throws RuntimeException
     * @throws DuplicateFieldException
     * @throws InvalidFieldException
     */
    public function handle(mixed $value): ?string
    {
        $value = trim($value);
        $value = DocumentHelper::remove_pontuation($value);

        return $value;
    }
}
