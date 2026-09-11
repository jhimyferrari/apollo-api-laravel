<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\User;
use App\Services\TreatmentService\Strategies\TreatRegularString;
use App\Services\TreatmentService\TreatmentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BrandService extends BaseService
{
    public function __construct(
        private readonly TreatmentService $treatment,
        private readonly TreatRegularString $treatString,
    ) {
        parent::__construct(new Brand);
    }

    public function create(array $data, User $user): Brand
    {
        $data['name'] = $this->treatment->for($this->treatString, $data['name'], 'name', $this->model)->mustBeNotNull()->mustBeUnique()->handle();
        if (isset($data['description'])) {
            $data['description'] = $this->treatment->for($this->treatString, $data['description'], 'description', $this->model)->handle();
        }
        $newBrand = new Brand($data);
        $newBrand->organization_id = $user->organization_id;
        $newBrand->save();

        return $newBrand;
    }

    /**
     * @param  Brand  $brand
     */
    public function update(Model $brand, array $data): Brand
    {

        if (isset($data['name'])) {
            $brand->name = $this->treatment->for($this->treatString, $data['name'], 'name', $this->model)
                ->mustBeNotNull()->mustBeUnique()->ignoredId($brand->id)->handle();
        }

        if (\array_key_exists('description', $data)) {
            $brand->description = $this->treatment->for($this->treatString, $data['description'], 'description', $this->model)->handle();
        }

        $brand->save();

        return $brand;
    }

    /**
     * Delete a Brand from database
     * and decouples all associated products
     *
     * @param  Brand  $model
     */
    public function delete(Model $model): void
    {
        DB::transaction(function () use ($model) {
            $model->products()->update(['brand_id' => null]);
            $model->delete();
        });
    }
}
