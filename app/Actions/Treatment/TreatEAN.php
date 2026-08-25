<?php

namespace App\Actions\Treatment;

use App\Actions\Validation\ValidateDuplicateField;
use App\Actions\Validation\ValidateEAN;
use App\Actions\Validation\ValidateFieldIsNotNull;
use App\Exceptions\DuplicateFieldException;
use App\Exceptions\InvalidFieldException;
use Illuminate\Database\Eloquent\Model;

class TreatEAN
{
    public function __construct(
        private ValidateFieldIsNotNull $validateFieldIsNotNull,
        private ValidateEAN $validateEAN,
        private ValidateDuplicateField $validateDuplicateField,
    ) {}

    /**
     * @throws InvalidFieldException
     * @throws DuplicateFieldException
     */
    public function execute(Model $model, string $field, mixed $value, bool $mustBeNotNull, bool $mustBeUnique, ?string $ignoredId = null, ?string $organizationId = null)
    {
        $value = trim($value);
        if ($mustBeNotNull) {
            $this->validateFieldIsNotNull->execute($value, $field);
        } elseif ($value == '') {
            return null;
        }
        $value = $this->validateEAN->execute($value);
        if ($mustBeUnique) {
            $this->validateDuplicateField->execute($model, $field, $value, $ignoredId, $organizationId);
        }

        return $value;
    }
}
