<?php

namespace App\Services;

use App\Actions\Validation\ValidateUnitProduct;
use App\Models\Product;
use App\Models\User;
use App\Services\TreatmentService\Strategies\TreatBrand;
use App\Services\TreatmentService\Strategies\TreatCategory;
use App\Services\TreatmentService\Strategies\TreatEAN;
use App\Services\TreatmentService\Strategies\TreatMoney;
use App\Services\TreatmentService\Strategies\TreatNCM;
use App\Services\TreatmentService\Strategies\TreatRegularString;
use App\Services\TreatmentService\TreatmentService;
use DB;
use Illuminate\Database\Eloquent\Model;

class ProductService extends BaseService
{
    public function __construct(
        private readonly TreatmentService $treatment,
        private readonly TreatRegularString $treatString,
        private readonly TreatEAN $treatEAN,
        private readonly TreatNCM $treatNcm,
        private readonly TreatCategory $treatCategory,
        private readonly TreatBrand $treatBrand,
        private readonly TreatMoney $treatMoney,
        private readonly ValidateUnitProduct $validateUnit
    ) {
        parent::__construct(new Product);
    }

    public function create(array $data, User $user): Product
    {

        $data['name'] = $this->treatment->for($this->treatString, $data['name'], 'name', $this->model)->mustBeNotNull()->handle();

        if (isset($data['ean'])) {
            $data['ean'] = $this->treatment->for($this->treatEAN, $data['ean'], 'ean', $this->model)->mustBeUnique()->handle();
        }
        if (isset($data['unit'])) {
            $this->validateUnit->execute($data['unit']);
        }

        $data['cost_price'] = $this->treatment->for($this->treatMoney, $data['cost_price'], 'cost_price', $this->model)->handle();
        $data['sale_price'] = $this->treatment->for($this->treatMoney, $data['sale_price'], 'sale_price', $this->model)->handle();

        $newProduct = new Product($data);

        $newProduct->brand_id = (isset($data['brand_id']))
        ? $this->treatment->for($this->treatBrand, $data['brand_id'], 'brand_id', $this->model)->handle()?->id : null;

        $newProduct->ncm_code_id = (isset($data['ncm'])) ?
        $this->treatment->for($this->treatNcm, $data['ncm'], 'ncm', $this->model)->handle()?->id : null;

        $newProduct->organization_id = $user->organization_id;

        DB::transaction(function () use ($newProduct, $data) {
            $newProduct->save();

            if (! empty($data['categories'])) {

                $newProduct->categories()->sync(
                    $this->treatment->for($this->treatCategory, $data['categories'], 'categories', $this->model)->handle()
                );
            }
        });

        return $newProduct;

    }

    /**
     * @param  Product  $product
     */
    public function update(Model $product, array $data): Product
    {

        if (\array_key_exists('name', $data)) {
            $product->name = $this->treatment->for($this->treatString, $data['name'], 'name', $this->model)->mustBeNotNull()->handle();
        }
        if (\array_key_exists('ean', $data)) {
            $product->ean = $this->treatment->for($this->treatEAN, $data['ean'], 'ean', $this->model)
                ->mustBeUnique()->ignoredId($product->id)->handle();
        }
        if (\array_key_exists('unit', $data)) {
            app(ValidateUnitProduct::class)->execute($data['unit']);
            $product->unit = $data['unit'];
        }
        if (\array_key_exists('ncm', $data)) {
            $product->ncm_code_id = $this->treatment->for($this->treatNcm, $data['ncm'], 'ncm', $this->model)->handle()?->id;
        }
        if (\array_key_exists('brand_id', $data)) {
            $product->brand_id = $this->treatment->for($this->treatBrand, $data['brand_id'], 'brand_id', $this->model)->handle()?->id;
        }
        if (\array_key_exists('cost_price', $data)) {
            $product->cost_price = $this->treatment->for($this->treatMoney, $data['cost_price'], 'cost_price', $this->model)->handle();

        }
        if (\array_key_exists('sale_price', $data)) {
            $product->sale_price = $this->treatment->for($this->treatMoney, $data['sale_price'], 'sale_price', $this->model)->handle();
        }

        DB::transaction(function () use ($product, $data) {
            $product->save();

            if (\array_key_exists('categories', $data)) {
                $product->categories()->sync(
                    $this->treatment->for($this->treatCategory, $data['categories'], 'categories', $this->model)->handle()
                );
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
