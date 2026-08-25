<?php

namespace App\Services;

use App\Actions\Treatment\TreatBrand;
use App\Actions\Treatment\TreatCategory;
use App\Actions\Treatment\TreatEAN;
use App\Actions\Treatment\TreatName;
use App\Actions\Treatment\TreatNCM;
use App\Actions\Validation\ValidateUnitProduct;
use App\Models\Product;
use App\Models\User;
use App\ValueObjects\Money;
use DB;
use Illuminate\Database\Eloquent\Model;

class ProductService extends BaseService
{
    public function __construct(
        private TreatName $treatName,
        private TreatEAN $treatEAN,
        private TreatNCM $treatNcm,
        private TreatCategory $treatCategory,
        private TreatBrand $treatBrand
    ) {
        parent::__construct(new Product);
    }

    public function create(array $data, User $user): Product
    {

        $data['name'] = $this->treatName->execute(
            $this->model,
            'name',
            $data['name'],
            mustBeNotNull: true,
            mustBeUnique: false
        );

        if (isset($data['ean'])) {
            $data['ean'] = $this->treatEAN->execute(
                $this->model,
                'ean',
                $data['ean'],
                mustBeNotNull: false,
                mustBeUnique: true
            );
        }
        if (isset($data['unit'])) {
            app(ValidateUnitProduct::class)->execute($data['unit']);
        }

        $data['cost_price'] = Money::fromDecimal($data['cost_price']);
        $data['sale_price'] = Money::fromDecimal($data['sale_price']);

        $newProduct = new Product($data);

        $newProduct->brand_id = (isset($data['brand_id'])) ? $this->treatBrand->execute($data['brand_id'], mustBeNotNull: false)?->id : null;

        $newProduct->ncm_code_id = (isset($data['ncm'])) ? $this->treatNcm->execute($data['ncm'], mustBeNotNull: false)?->id : null;

        $newProduct->organization_id = $user->organization_id;

        DB::transaction(function () use ($newProduct, $data) {
            $newProduct->save();

            if (! empty($data['categories'])) {
                $newProduct->categories()->sync($this->treatCategory->execute($data['categories']));
            }
        });

        return $newProduct;

    }

    /**
     * @param  Product  $product
     */
    public function update(Model $product, array $data): Product
    {

        if (isset($data['name'])) {
            $product->name = $this->treatName->execute(
                $this->model,
                'name',
                $data['name'],
                mustBeNotNull: true,
                mustBeUnique: false
            );
        }
        if (isset($data['ean'])) {
            $product->ean = $this->treatEAN->execute(
                $this->model,
                'ean',
                $data['ean'],
                mustBeNotNull: false,
                mustBeUnique: true,
                ignoredId: $product->id
            );
        }
        if (isset($data['unit'])) {
            app(ValidateUnitProduct::class)->execute($data['unit']);
            $product->unit = $data['unit'];
        }
        if (isset($data['ncm'])) {
            $product->ncm_code_id = $this->treatNcm->execute($data['ncm'], mustBeNotNull: false)?->id;
        }
        if (\array_key_exists('brand_id', $data)) {
            $product->brand_id = $this->treatBrand->execute($data['brand_id'], mustBeNotNull: false)?->id;
        }
        if (isset($data['cost_price'])) {
            $product->cost_price = Money::fromDecimal($data['cost_price']);

        }
        if (isset($data['sale_price'])) {
            $product->sale_price = Money::fromDecimal($data['sale_price']);

        }

        DB::transaction(function () use ($product, $data) {
            $product->save();

            if (\array_key_exists('categories', $data)) {
                $product->categories()->sync($this->treatCategory->execute($data['categories']));
            }
        });

        return $product;
    }

    /**
     * @param  Product  $product
     */
    public function delete(Model $product): void
    {

        DB::transaction(function () use ($product) {
            $product->categories()->detach();
            $product->delete();

        });
    }
}
