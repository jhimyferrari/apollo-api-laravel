<?php

namespace App\Actions\Treatment;

use App\Actions\Validation\ValidateFieldIsNotNull;
use App\Models\Brand;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use RuntimeException;

class TreatBrand
{
    public function __construct(
        private ValidateFieldIsNotNull $validateFieldIsNotNull
    ) {}

    public function execute(?string $value, bool $mustBeNotNull = false): ?Brand
    {
        if ($mustBeNotNull) {
            $this->validateFieldIsNotNull->execute($value, 'brand_id');
        }
        if ($value == null) {
            return null;
        }
        try {
            $brand = Brand::findOrFail($value);

            return $brand;

        } catch (ModelNotFoundException|QueryException $e) {
            throw new ModelNotFoundException("Brand id $value not found");
        } catch (Exception $e) {
            report($e);
            throw new RuntimeException('Something went wrong, please, contact support');
        }

    }
}
