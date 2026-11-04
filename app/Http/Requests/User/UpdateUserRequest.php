<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
                'required_without_all:email,permissions',
                'string',
            ],
            'email' => [
                'required_without_all:name,permissions',
                'email',
            ],
            'permissions' => [
                'required_without_all:name,email',
                'array',
            ],
            'permissions.*' => [
                'required_with:permissions',
                'string',
                'exists:permissions,name',
            ],
        ];
    }
}
