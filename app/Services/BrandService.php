<?php

namespace App\Services;

use App\Actions\Treatment\TreatName;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BrandService extends BaseService
{
    public function __construct(
        private TreatName $treatName
    ) {
        parent::__construct(new Brand);
    }

    public function create(array $data, User $user): Brand
    {
        $data['name'] = $this->treatName->execute(
            $this->model,
            'name',
            $data['name'],
            mustBeNotNull: true,
            mustBeUnique: true,
        );
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
            $brand->name = $this->treatName->execute(
                $this->model,
                'name',
                $data['name'],
                mustBeNotNull: true,
                mustBeUnique: true,
                ignoredId: $brand->id
            );
        }

        if (isset($data['description'])) {
            $formated = trim($data['description']);
            $brand->description = ($formated != '') ? $formated : null;
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
