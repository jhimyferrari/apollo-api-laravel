<?php

namespace App\Actions\Treatment;

use App\Actions\Validation\ValidateDuplicateField;
use App\Actions\Validation\ValidateFieldIsNotNull;
use App\Exceptions\DuplicateFieldException;
use App\Exceptions\InvalidFieldException;
use App\Helpers\DocumentHelper;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class TreatStateRegistration
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
    public function execute(Model $model, string $field, string $value, bool $mustBeNotNull = true, bool $mustBeUnique = true, ?string $ignoredId = null, ?string $organizationId = null): ?string
    {
        $value = trim($value);
        $value = DocumentHelper::remove_pontuation($value);

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
