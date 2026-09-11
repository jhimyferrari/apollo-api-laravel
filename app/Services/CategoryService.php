<?php

namespace App\Services;

use App\Models\Category;
use App\Models\User;
use App\Services\TreatmentService\Strategies\TreatRegularString;
use App\Services\TreatmentService\TreatmentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CategoryService extends BaseService
{
    public function __construct(
        private readonly TreatmentService $treatment,
        private readonly TreatRegularString $treatString,
    ) {
        parent::__construct(new Category);
    }

    public function create(array $data, User $user): Category
    {

        $data['name'] = $this->treatment->for($this->treatString, $data['name'], 'name', $this->model)->mustBeUnique()->mustBeNotNull()->handle();

        if (isset($data['description'])) {
            $data['description'] = $this->treatment->for($this->treatString, $data['description'], 'description', $this->model)->handle();
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
        if (\array_key_exists('name', $data)) {
            $category->name = $this->treatment->for($this->treatString, $data['name'], 'name', $this->model)
                ->mustBeUnique()->mustBeNotNull()->ignoredId($category->id)->handle();
        }
        if (\array_key_exists('description', $data)) {
            $category->description = $this->treatment->for($this->treatString, $data['description'], 'description', $this->model)->handle();
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
