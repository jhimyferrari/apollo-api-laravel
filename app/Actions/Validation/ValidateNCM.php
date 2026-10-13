<?php

namespace App\Actions\Validation;

use App\Actions\Fetch\Ncm\FetchNcmFromReceita;
use App\Models\NcmCode;
use Illuminate\Validation\ValidationException;

class ValidateNCM
{
    public function __construct(
        private FetchNcmFromReceita $fetch) {}

    public function execute(string $value, string $field): NcmCode
    {
        if (! preg_match_all('/^\d{8}$/', $value)) {
            throw ValidationException::withMessages([$field => __('validation.regex', ['attribute' => 'ncm'])]);
        }
        $ncm = NcmCode::where('code', $value)->first();
        if ($ncm != null) {
            if (! $ncm->isActive) {
                throw ValidationException::withMessages([$field => __('validation.ncm.inactive', ['value' => $value])]);
            }

            return $ncm;
        }

        $ncm = $this->fetch->execute($value);
        if ($ncm == null) {
            throw ValidationException::withMessages([$field => __('validation.ncm.not_found', ['value' => $value])]);
        }

        return $ncm;
    }
}
