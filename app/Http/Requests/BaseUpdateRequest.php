<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

abstract class BaseUpdateRequest extends FormRequest
{
    /**
     * Configure the validator instance to reject empty update payloads.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isEmpty() && empty($validator->validated())) {
                    $validator->errors()->add('payload', __('validation.empty_update_payload'));
                }
            },
        ];
    }
}
