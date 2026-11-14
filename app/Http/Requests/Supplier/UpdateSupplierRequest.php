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
                new CpfAndCnpj,
            ],
            'legal_name' => [
                'sometimes',
                'string',
            ],
            'trade_name' => [
                'sometimes',
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
