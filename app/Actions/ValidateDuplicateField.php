<?php

namespace App\Actions;

use App\Exceptions\DuplicateFieldException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ValidateDuplicateField
{
    public function execute(Model $model, string $field, mixed $value, string $organizationId, ?string $ignoredId = null)
    {
        if (! Schema::hasColumn($model->getTable(), $field)) {
            throw new RuntimeException("Attribute `$field` does not exist in ".class_basename($model));
        }
        $findResults = $model->newQuery()->where($field, $value)->where('organization_id', $organizationId);

        if ($ignoredId !== null) {
            $findResults->where('id', '=!', $ignoredId);
        }

        if ($findResults->exists()) {
            throw new DuplicateFieldException(class_basename($model)." $field `$value` already exists");
        }

    }
}
