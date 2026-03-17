<?php

namespace App\Http\Requests\Organization;

use App\Rules\CpfAndCnpj;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class storeOrganizationRequest extends FormRequest
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
                'required',
                'string', ],
            'document' => [
                'required',
                new CpfAndCnpj,
                'unique:organizations,document',
            ],
            'email' => [
                'required',
                'email'],
            'password' => [
                'required',
                'min:8',
            ],
        ];
    }
}
