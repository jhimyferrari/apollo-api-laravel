<?php

namespace App\Actions\Validation;

use App\Exceptions\DuplicateFieldException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Action for validate if exist some duplicate value in a field on database
 *
 * When using in a Auth context, it will be scopedBy `OrganizationScope`
 * based on the Model that is passed.
 * When using in a non-Auth context, you may declare the organizationId input
 */
class ValidateDuplicateField
{
    /**
     * @throws RuntimeException
     * @throws DuplicateFieldException
     */
    public function execute(Model $model, string $field, mixed $value, ?string $ignoredId = null, ?string $organizationId = null)
    {
        if (! Schema::hasColumn($model->getTable(), $field)) {
            throw new RuntimeException("Attribute `$field` does not exist in ".class_basename($model));
        }
        $findResults = $model->newQuery()->where($field, $value);

        if ($organizationId !== null) {
            if (! Schema::hasColumn($model->getTable(), 'organization_id')) {
                throw new RuntimeException('Attribute `organization_id` does not exist in '.class_basename($model));
            }
            $findResults->where('organization_id', $organizationId);
        }
        if ($ignoredId !== null) {
            $findResults->where('id', '!=', $ignoredId);
        }

        if ($findResults->exists()) {
            throw new DuplicateFieldException(class_basename($model)." $field `$value` already exist");
        }

    }
}
