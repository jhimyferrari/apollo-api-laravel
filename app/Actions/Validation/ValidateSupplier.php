<?php

namespace App\Actions\Validation;

use App\Models\Supplier;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ValidateSupplier
{
    public function execute(mixed $value, string $field): Supplier
    {

        try {
            $supplier = Supplier::findOrFail($value);
        } catch (ModelNotFoundException) {
            report(new ModelNotFoundException(__('errors.not_found', ['value' => $value, 'attribute' => $field])));
            throw ValidationException::withMessages([$field => __('validation.supplier', ['attribute' => __('validation.attributes.supplier_id')])]);
        } catch (Exception $e) {
            report($e);
            throw new RuntimeException(__('errors.internal_error'));
        }

        return $supplier;
    }
}
