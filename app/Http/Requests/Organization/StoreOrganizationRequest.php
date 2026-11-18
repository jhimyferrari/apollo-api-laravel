<?php

namespace App\Http\Requests\Organization;

use App\Helpers\DocumentHelper;
use App\Rules\CpfAndCnpj;
use App\Rules\StrongPassword;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->document)) {
            $this->merge([
                'document' => DocumentHelper::remove_pontuation($this->document),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'document' => [
                'required',
                'string',
                new CpfAndCnpj,
                'unique:organizations,document',
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                new StrongPassword,
            ],
        ];
    }
}
