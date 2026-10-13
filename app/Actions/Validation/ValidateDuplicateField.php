<?php

namespace App\Actions\Validation;

use App\Exceptions\DuplicateFieldException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
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
            throw new RuntimeException(__('errors.attribute_doesnt_exists_on_model',
                ['attribute' => $field, 'model' => \class_basename($model)]));
        }
        $findResults = $model->newQuery()->where($field, $value);

        if ($organizationId !== null) {
            if (! Schema::hasColumn($model->getTable(), 'organization_id')) {
                throw new RuntimeException(__('errors.attribute_doesnt_exists_on_model',
                    ['attribute' => 'organization_id', 'model' => \class_basename($model)]));
            }
            $findResults->where('organization_id', $organizationId);
        }
        if ($ignoredId !== null) {
            $findResults->where('id', '!=', $ignoredId);
        }

        if ($findResults->exists()) {
            throw ValidationException::withMessages([$field => __('validation.unique', ['attribute' => __("validation.attributes.$field")])]);
        }

    }
}
