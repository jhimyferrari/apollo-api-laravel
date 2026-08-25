<?php

namespace App\Actions\Treatment;

use App\Actions\Validation\ValidateFieldIsNotNull;
use App\Actions\Validation\ValidateNCM;
use App\Models\NcmCode;

class TreatNCM
{
    public function __construct(
        private ValidateNCM $validateNcm,
        private ValidateFieldIsNotNull $validateFielIsNotNull,
    ) {}

    public function execute(string $value, bool $mustBeNotNull): ?NcmCode
    {
        if ($mustBeNotNull) {
            $this->validateFielIsNotNull->execute('ncm', $value);
        }
        if ($value == null) {
            return null;
        }
        $ncm = $this->validateNcm->execute($value);

        return $ncm;
    }
}
