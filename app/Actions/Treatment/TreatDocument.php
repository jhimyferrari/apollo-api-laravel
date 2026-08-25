<?php

namespace App\Actions\Treatment;

use App\Actions\Validation\ValidateDocument;
use App\Actions\Validation\ValidateDuplicateField;
use App\Actions\Validation\ValidateFieldIsNotNull;
use App\Exceptions\DuplicateFieldException;
use App\Exceptions\InvalidFieldException;
use App\Helpers\DocumentHelper;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Action for validate and formate documents
 */
class TreatDocument
{
    public function __construct(
        private ValidateFieldIsNotNull $validateFieldIsNotNull,
        private ValidateDocument $validateDocument,
        private ValidateDuplicateField $validateDuplicatedField,
    ) {}

    /**
     * @throws InvalidFieldException
     * @throws RuntimeException
     * @throws DuplicateFieldException
     */
    public function execute(Model $model, string $field, string $value, ?string $ignoredId = null, ?string $organizationId = null): ?string
    {

        $this->validateFieldIsNotNull->execute($value, $field);
        $value = DocumentHelper::remove_pontuation($value);
        $this->validateDocument->execute($value);
        $this->validateDuplicatedField->execute($model, $field, $value, $ignoredId, $organizationId);

        return $value;
    }
}
