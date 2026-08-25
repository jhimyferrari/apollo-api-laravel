<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

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
                'required',
                'string'],
            'password' => [
                'required',
                'min:8'],
            'email' => [
                'required',
                'email',
            ],
            'permissions' => [
                'nullable',
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
