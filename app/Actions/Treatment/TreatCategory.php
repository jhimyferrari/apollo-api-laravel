<?php

namespace App\Actions\Treatment;

use App\Actions\Validation\ValidateFieldIsNotNull;
use App\Exceptions\ResourceNotFoundException;
use App\Models\Category;
use Illuminate\Support\Collection;

class TreatCategory
{
    public function __construct(
        private ValidateFieldIsNotNull $validateFieldIsNotNull
    ) {}

    /**
     * @throws ResourceNotFoundException
     */
    public function execute(array $array, bool $mustBeNotNull = false): ?Collection
    {
        $categoriesId = collect($array)->pluck('id')->toArray();
        if ($mustBeNotNull) {
            $this->validateFieldIsNotNull->execute($categoriesId, 'categories');
        }
        if (empty($categoriesId)) {
            return null;
        }
        $categories = Category::findMany($categoriesId);
        if ($categories->pluck('id')->count() != \count($categoriesId)) {
            $invalidId = array_diff($categoriesId, $categories->pluck('id')->toArray());
            throw new ResourceNotFoundException('Categories not found: '.implode(',', $invalidId));
        }

        return $categories;

    }
}
