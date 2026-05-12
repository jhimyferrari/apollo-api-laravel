<?php

namespace App\Http\Requests\Supplier;

use App\Rules\CpfAndCnpj;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierRequest extends FormRequest
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
            'document' => [
                'required',
                new CpfAndCnpj,
            ],
            'legal_name' => [
                'required',
                'string',
            ],
            'trade_name' => [
                'required',
                'string',
            ],
            'state_registration' => [
                'nullable',
                'string',
            ],
            'email' => [
                'nullable',
                'email',
            ],
            'phone' => [
                'nullable',
                'string',
            ],

        ];

    }
}
