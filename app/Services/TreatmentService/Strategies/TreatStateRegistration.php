<?php

namespace App\Services\TreatmentService\Strategies;

use App\Actions\Validation\ValidateDuplicateField;
use App\Actions\Validation\ValidateFieldIsNotNull;
use App\Exceptions\DuplicateFieldException;
use App\Exceptions\InvalidFieldException;
use App\Helpers\DocumentHelper;
use Illuminate\Validation\ValidationException;
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
    public function handle(mixed $value, string $field): ?string
    {
        if (! \is_string($value)) {
            throw ValidationException::withMessages([$field => __('validation.string', ['attribute' => __("validation.attributes.$field")])]);
        }

        $value = trim($value);
        $value = DocumentHelper::remove_pontuation($value);

        if ($value === '') {
            throw ValidationException::withMessages([$field => __('validation.regex', ['attribute' => __("validation.attributes.$field")])]);
        }

        return $value;
    }
}
