<?php

namespace App\Actions\Validation;

use App\Models\Client;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ValidateClient
{
    public function execute(mixed $value, string $field): Client
    {

        try {
            $client = Client::findOrFail($value);
        } catch (ModelNotFoundException) {
            report(new ModelNotFoundException(__('errors.not_found', ['value' => $value, 'attribute' => $field])));
            throw ValidationException::withMessages([$field => __('validation.client', ['attribute' => __('validation.attributes.client_id')])]);
        } catch (Exception $e) {
            report($e);
            throw new RuntimeException(__('errors.internal_error'));
        }

        return $client;
    }
}
