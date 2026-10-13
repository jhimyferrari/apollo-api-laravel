<?php

namespace App\Actions\Validation;

use App\Models\Seller;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ValidateSeller
{
    public function execute(mixed $value, string $field): Seller
    {

        try {
            $seller = Seller::findOrFail($value);
        } catch (ModelNotFoundException) {
            report(new ModelNotFoundException(__('errors.not_found', ['value' => $value, 'attribute' => $field])));
            throw ValidationException::withMessages([$field => __('validation.seller', ['attribute' => __('validation.attributes.seller_id')])]);
        } catch (Exception $e) {
            report($e);
            throw new RuntimeException(__('errors.internal_error'));
        }

        return $seller;
    }
}
