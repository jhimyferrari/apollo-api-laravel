<?php

namespace App\Services;

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
        $newProduct = new Product;
        $newProduct->name = $data['name'];
        $newProduct->description = $data['description'] ?? null;
        $newProduct->unit = $data['unit'];
        $newProduct->ncm = $data['ncm'] ?? null;
        $newProduct->ean = $data['ean'] ?? null;
        $newProduct->cost_price = $data['cost_price'];
        $newProduct->sale_price = $data['sale_price'];

        if (isset($data['brand_id'])) {
            $brand = Brand::findOrFail($data['brand_id']);
            $newProduct->brand_id = $brand->id;
        }
        $categoriesId = [];
        if (! empty($data['categories'])) {
            $categoriesId = collect($data['categories'])->pluck('id')->toArray();
            $categories = Category::findMany($categoriesId);

            if ($categories->pluck('id')->count() != \count($categoriesId)) {
                $invalid_id = array_diff($categories->pluck('id')->toArray(), $categoriesId);
                throw new ResourceNotFoundException('Categories not found: '.implode(', ', $invalid_id));
            }
        }
        $newProduct->organization_id = $user->organization_id;
        $newProduct->save();

        if (! empty($categoriesId)) {
            $newProduct->categories()->sync($categoriesId);
        }

        return $newProduct;

    }
}
