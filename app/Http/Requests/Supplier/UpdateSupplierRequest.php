<?php

namespace App\Http\Requests\Supplier;

use App\Enum\Status\SupplierStatus;
use App\Http\Requests\BaseUpdateRequest;
use App\Rules\CpfAndCnpj;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends BaseUpdateRequest
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
            'status' => [
                'sometimes',
                Rule::enum(SupplierStatus::class),
            ],
            'document' => [
                'sometimes',
                'string',
                new CpfAndCnpj,
            ],
            'legal_name' => [
                'sometimes',
                'string',
                'max:255',
            ],
            'trade_name' => [
                'sometimes',
                'string',
                'max:255',
            ],
            'state_registration' => [
                'nullable',
                'string',
                'max:255',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:255',
            ],

        ];

    }
}
