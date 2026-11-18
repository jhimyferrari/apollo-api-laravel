<?php

namespace App\Http\Requests\Report;

use App\Enum\Status\OrderStatus;
use App\Enum\Status\PaymentStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalesReportSummaryRequest extends FormRequest
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
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'date_field' => ['nullable', 'string', 'in:confirmed_at,created_at'],
            'status' => ['nullable', 'string', Rule::in([...OrderStatus::allValues(), 'all'])],
            'payment_status' => ['nullable', 'string', Rule::enum(PaymentStatus::class)],
            'client_id' => [
                'nullable',
                'uuid',
                Rule::exists('clients', 'id')
                    ->where('organization_id', $this->user()?->organization_id)
                    ->whereNull('deleted_at'),
            ],
            'seller_id' => [
                'nullable',
                'uuid',
                Rule::exists('sellers', 'id')
                    ->where('organization_id', $this->user()?->organization_id)
                    ->whereNull('deleted_at'),
            ],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
