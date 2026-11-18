<?php

namespace App\Http\Requests\Address;

use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Requests\BaseUpdateRequest;
use App\Models\Address;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdateAddressRequest extends BaseUpdateRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $address = $this->route('address');

        if ($address instanceof Address) {
            AddressController::verifyPermission($address->addressable, 'update');
        }

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

            'street' => ['sometimes', 'string', 'max:45'],
            'number' => ['sometimes', 'string', 'max:10'],
            'neighborhood' => ['sometimes', 'string', 'max:45'],
            'city_ibge_code' => ['sometimes', 'string', 'max:7'],
            'cep' => ['sometimes', 'string', 'max:9'],
            'is_default' => ['nullable', 'boolean'],
            'complement' => ['nullable', 'string', 'max:45'],
        ];
    }
}
