<?php

namespace App\Services;

use App\Actions\Treatment\TreatName;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CategoryService extends BaseService
{
    public function __construct(
        private TreatName $treatName
    ) {
        parent::__construct(new Category);
    }

    public function create(array $data, User $user): Category
    {

        $data['name'] = $this->treatName->execute(
            $this->model,
            'name',
            $data['name'],
            mustBeNotNull: true,
            mustBeUnique: true
        );

        if (isset($data['description'])) {
            $formated = trim($data['description']);
            $data['description'] = ($formated != '') ? $formated : null;
        }
        $newCategory = new Category($data);
        $newCategory->organization_id = $user->organization_id;
        $newCategory->save();

        return $newCategory;
    }

    /**
     * @param  Category  $category
     */
    public function update(Model $category, array $data): Category
    {

        if (isset($data['name'])) {
            $category->name = $this->treatName->execute(
                $this->model,
                'name',
                $data['name'],
                mustBeNotNull: true,
                mustBeUnique: true,
                ignoredId: $category->id
            );
        }
        if (isset($data['description'])) {
            $formated = trim($data['description']);
            $category->description = ($formated != '') ? $formated : null;
        }
        $category->save();

        return $category;
    }

    /**
     * Delete a Category from database
     * and decouples all associated products
     *
     * @param  Category  $model
     */
    public function delete(Model $model): void
    {
        DB::transaction(function () use ($model) {
            $model->products()->detach();
            $model->delete();
        });
    }
}
