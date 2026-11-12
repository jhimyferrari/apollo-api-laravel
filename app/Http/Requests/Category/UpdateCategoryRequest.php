<?php

namespace App\Http\Requests\Category;

use App\Http\Requests\BaseUpdateRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdateCategoryRequest extends BaseUpdateRequest
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
            'name' => [
                'sometimes',
                'string',
            ],
            'description' => [
                'nullable',
                'string',
            ],
        ];
    }
}
