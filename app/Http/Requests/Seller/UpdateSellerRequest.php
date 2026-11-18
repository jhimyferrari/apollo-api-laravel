<?php

namespace App\Http\Requests\Seller;

use App\Enum\Status\SellerStatus;
use App\Http\Requests\BaseUpdateRequest;
use App\Models\Seller;
use App\Rules\CpfAndCnpj;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

            'started_at' => [
                'sometimes',
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

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            ...parent::after(),
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['started_at', 'ended_at'])) {
                    return;
                }

                $seller = $this->route('seller');
                $startedAt = $this->input('started_at') ?? ($seller instanceof Seller ? $seller->started_at?->toDateString() : null);

                $endedAt = \array_key_exists('ended_at', $this->all())
                    ? $this->input('ended_at')
                    : ($seller instanceof Seller ? $seller->ended_at?->toDateString() : null);

                if ($startedAt !== null && $endedAt !== null && Carbon::parse($endedAt)->startOfDay()->lt(Carbon::parse($startedAt)->startOfDay())) {
                    $validator->errors()->add('ended_at', __('validation.after_or_equal', [
                        'attribute' => __('validation.attributes.ended_at'),
                        'date' => __('validation.attributes.started_at'),
                    ]));
                }
            },
        ];
    }
}
