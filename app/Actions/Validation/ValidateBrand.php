<?php

namespace App\Actions\Validation;

use App\Exceptions\ResourceNotFoundException;
use App\Models\Brand;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ValidateBrand
{
    public function execute(mixed $value, string $field): Brand
    {

        try {
            $brand = Brand::findOrFail($value);
        } catch (ModelNotFoundException|QueryException) {
            report(new ResourceNotFoundException(__('errors.not_found', ['value' => $value, 'attribute' => $field])));
            throw ValidationException::withMessages([$field => __('validation.city', ['attribute' => __('validation.attributes.brand_id')])]);
        } catch (Exception $e) {
            report($e);
            throw new RuntimeException(__('errors.internal_error'));
        }

        return $brand;
    }
}
