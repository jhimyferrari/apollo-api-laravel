<?php

namespace App\Actions\Treatment;

use App\Actions\Validation\ValidateDuplicateField;
use App\Actions\Validation\ValidateFieldIsNotNull;
use App\Exceptions\DuplicateFieldException;
use App\Exceptions\InvalidFieldException;
use Illuminate\Database\Eloquent\Model;

class TreatName
{
    public function __construct(
        private ValidateFieldIsNotNull $validateFieldIsNotNull,
        private ValidateDuplicateField $validateDuplicateField
    ) {}

    /**
     * @throws InvalidFieldException
     * @throws DuplicateFieldException
     */
    public function execute(Model $model, string $field, string $value, bool $mustBeNotNull, bool $mustBeUnique, ?string $ignoredId = null, ?string $organizationId = null): ?string
    {
        $value = trim($value);
        if ($mustBeNotNull) {
            $this->validateFieldIsNotNull->execute($value, $field);
        } elseif ($value == '') {
            return null;
        }
        if ($mustBeUnique) {
            $this->validateDuplicateField->execute($model, $field, $value, $ignoredId, $organizationId);
        }

        return $value;
    }
}
