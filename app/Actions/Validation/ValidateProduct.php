<?php

namespace App\Actions\Validation;

use App\Exceptions\ResourceNotFoundException;
use App\Models\Product;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ValidateProduct
{
    public function execute(mixed $value, string $field): Product
    {
        try {
            return Product::findOrFail($value);
        } catch (ModelNotFoundException|QueryException) {
            report(new ResourceNotFoundException("Product id $value not found"));
            throw ValidationException::withMessages([$field => __('validation.product', ['attribute' => $field])]);
        } catch (Exception $e) {
            report($e);
            throw new RuntimeException(__('errors.internal_error'));
        }
    }
}
