<?php

namespace App\Services;

use App\Actions\ValidateDuplicateField;
use App\Exceptions\ResourceNotFoundException;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;

class ProductService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new Product);
    }

    public function create(array $data, User $user): Product
    {

        $formatedName = trim($data['name']);
        app(ValidateDuplicateField::class)->execute(new Product, 'name', $formatedName, $user->organization_id);

        $data['name'] = $formatedName;

        $categoriesId = [];
        if (! empty($data['categories'])) {
            $categoriesId = collect($data['categories'])->pluck('id')->toArray();
            $categories = Category::findMany($categoriesId);

            if ($categories->pluck('id')->count() != \count($categoriesId)) {
                $invalid_id = array_diff($categories->pluck('id')->toArray(), $categoriesId);
                throw new ResourceNotFoundException('Categories not found: '.implode(', ', $invalid_id));
            }
        }
        if (isset($data['brand_id'])) {
            Brand::findOrFail($data['brand_id']);
        }

        $newProduct = new Product($data);
        $newProduct->organization_id = $user->organization_id;
        $newProduct->save();

        if (! empty($categoriesId)) {
            $newProduct->categories()->sync($categoriesId);
        }

        return $newProduct;

    }
}
