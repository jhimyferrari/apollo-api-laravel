<?php

namespace App\Http\Requests\Address;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
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

            'street' => ['required', 'string', 'max:45'],
            'number' => ['required', 'string', 'max:10'],
            'neighborhood' => ['required', 'string', 'max:45'],
            'city_ibge_code' => ['required', 'string', 'max:7'],
            'cep' => ['required', 'string', 'max:9'],
            'is_default' => ['nullable', 'boolean'],
            'complement' => ['nullable', 'string', 'max:45'],
        ];
    }
}
