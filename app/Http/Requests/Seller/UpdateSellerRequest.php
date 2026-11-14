<?php

namespace App\Http\Requests\Seller;

use App\Enum\Status\SellerStatus;
use App\Http\Requests\BaseUpdateRequest;
use App\Rules\CpfAndCnpj;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateSellerRequest extends BaseUpdateRequest
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
                Rule::enum(SellerStatus::class),
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
                    ['after_or_equal:started_at']
                ),
            ],

        ];
    }
}
