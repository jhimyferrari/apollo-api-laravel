<?php

namespace App\Http\Requests\Seller;

use App\Rules\CpfAndCnpj;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSellerRequest extends FormRequest
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

            'started_at' => [
                'nullable',
                'date',
            ],

            'ended_at' => [
                'nullable',
                'date',
                Rule::when(
                    fn () => filled($this->started_at),
                    ['after_or_equal:starts_at']
                ),
            ],

        ];
    }
}
