<?php

namespace App\Services;

use App\Actions\ValidateDuplicateField;
use App\Models\Brand;
use App\Models\User;

class BrandService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new Brand);
    }

    public function create(array $data, User $user): Brand
    {
        $formatedName = trim($data['name']);
        app(ValidateDuplicateField::class)->execute(new Brand, 'name', $formatedName, $user->organization_id);

        $data['name'] = $formatedName;
        $newBrand = new Brand($data);
        $newBrand->organization_id = $user->organization_id;
        $newBrand->save();

        return $newBrand;
    }
}
