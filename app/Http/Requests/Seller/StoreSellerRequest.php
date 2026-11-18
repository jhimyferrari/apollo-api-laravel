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
                'string',
                new CpfAndCnpj,
            ],
            'legal_name' => [
                'required',
                'string',
                'max:255',
            ],
            'trade_name' => [
                'required',
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

            'started_at' => [
                'nullable',
                'date',
                'after_or_equal:1900-01-01',
                'before_or_equal:+30 days',
            ],

            'ended_at' => [
                'nullable',
                'date',
                Rule::when(
                    fn () => filled($this->started_at),
                    ['after_or_equal:started_at'],
                    ['after_or_equal:today'],
                ),
            ],

        ];
    }
}
