<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'organization_id' => [
                'required',
                'uuid',
                'exists:organizations,id',
            ],
            'email' => [
                'required',
                'email',
                Rule::unique('users')->where('organization_id', $this['organization_id']),
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
