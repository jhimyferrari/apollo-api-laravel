<?php

namespace App\Actions\Validation;

use App\Actions\Fetch\Ncm\FetchNcmFromReceita;
use App\Exceptions\InvalidFieldException;
use App\Models\NcmCode;

class ValidateNCM
{
    public function __construct(
        private FetchNcmFromReceita $fetch) {}

    public function execute(string $value): NcmCode
    {
        if (! preg_match_all('/^\d{8}$/', $value)) {
            throw new InvalidFieldException(__('validation.regex', ['attribute' => 'ncm']));
        }
        $ncm = NcmCode::where('code', $value)->first();
        if ($ncm != null) {
            if (! $ncm->isActive) {
                throw new InvalidFieldException(__('errors.ncm.inactive', ['ncm' => $value]));
            }

            return $ncm;
        }

        $ncm = $this->fetch->execute($value);
        if ($ncm == null) {
            throw new InvalidFieldException("The ncm `$value` does not exist or it`s not implemented yet");
        }

        return $ncm;
    }
}
