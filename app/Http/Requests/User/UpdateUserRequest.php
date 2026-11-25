<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseUpdateRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdateUserRequest extends BaseUpdateRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
            ],
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
            ],
            'password' => [
                'sometimes',
                'string',
                'min:8',
            ],
            'permissions' => [
                'sometimes',
                'array',
            ],
            'permissions.*' => [
                'required',
                'string',
                'distinct',
                'exists:permissions,name',
            ],
        ];
    }
}
