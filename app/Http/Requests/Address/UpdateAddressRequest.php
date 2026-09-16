<?php

namespace App\Http\Requests\Address;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
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

            'street' => ['nullable'],
            'number' => ['nullable'],
            'neighborhood' => ['nullable'],
            'city_ibge_code' => ['nullable'],
            'cep' => ['nullable'],
            'is_default' => ['nullable', 'boolean'],
            'complement' => ['nullable', 'string'],
        ];
    }
}
