<?php

namespace App\Services;

use App\Actions\ValidateDuplicateField;
use App\Models\Category;
use App\Models\User;

class CategoryService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new Category);
    }

    public function create(array $data, User $user): Category
    {

        $formatedName = trim($data['name']);
        app(ValidateDuplicateField::class)->execute(new Category, 'name', $formatedName, $user->organization_id);

        $data['name'] = $formatedName;
        $newCategory = new Category($data);
        $newCategory->organization_id = $user->organization_id;
        $newCategory->save();

        return $newCategory;
    }
}
