<?php

namespace App\Http\Controllers\Api\V1;

use App\Enum\PermissionType;
use App\Enum\Status\ClientStatus;
use App\Enum\Status\ProductStatus;
use App\Enum\Status\SellerStatus;
use App\Enum\Status\SupplierStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lookup\LookupByNumberRequest;
use App\Http\Requests\Lookup\LookupRequest;
use App\Http\Resources\Lookup\BrandLookupResource;
use App\Http\Resources\Lookup\CategoryLookupResource;
use App\Http\Resources\Lookup\ClientLookupResource;
use App\Http\Resources\Lookup\ProductLookupResource;
use App\Http\Resources\Lookup\SellerLookupResource;
use App\Http\Resources\Lookup\SupplierLookupResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Client;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LookupController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('ability:'.implode(',', [
                PermissionType::CLIENT_READ->value,
                PermissionType::SALES_ORDER_CREATE->value,
                PermissionType::SALES_ORDER_UPDATE->value,
                PermissionType::SALES_ORDER_READ->value,
            ]), only: ['clients', 'clientByNumber']),

            new Middleware('ability:'.implode(',', [
                PermissionType::SELLER_READ->value,
                PermissionType::SALES_ORDER_CREATE->value,
                PermissionType::SALES_ORDER_UPDATE->value,
                PermissionType::SALES_ORDER_READ->value,
            ]), only: ['sellers', 'sellerByNumber']),

            new Middleware('ability:'.implode(',', [
                PermissionType::PRODUCT_READ->value,
                PermissionType::SALES_ORDER_CREATE->value,
                PermissionType::SALES_ORDER_UPDATE->value,
                PermissionType::SALES_ORDER_READ->value,
                PermissionType::PURCHASE_ORDER_CREATE->value,
                PermissionType::PURCHASE_ORDER_UPDATE->value,
                PermissionType::PURCHASE_ORDER_READ->value,
            ]), only: ['products', 'productByNumber']),

            new Middleware('ability:'.implode(',', [
                PermissionType::SUPPLIER_READ->value,
                PermissionType::PURCHASE_ORDER_CREATE->value,
                PermissionType::PURCHASE_ORDER_UPDATE->value,
                PermissionType::PURCHASE_ORDER_READ->value,
            ]), only: ['suppliers', 'supplierByNumber']),

            new Middleware('ability:'.implode(',', [
                PermissionType::BRAND_READ->value,
                PermissionType::PRODUCT_CREATE->value,
                PermissionType::PRODUCT_UPDATE->value,
            ]), only: ['brands', 'brandByNumber']),

            new Middleware('ability:'.implode(',', [
                PermissionType::CATEGORY_READ->value,
                PermissionType::PRODUCT_CREATE->value,
                PermissionType::PRODUCT_UPDATE->value,
            ]), only: ['categories', 'categoryByNumber']),
        ];
    }

    public function clients(LookupRequest $request): AnonymousResourceCollection
    {
        $query = Client::query();

        $status = $this->resolveStatusFilter($request, ClientStatus::allValues());
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('trade_name', 'ilike', "%{$search}%")
                    ->orWhere('legal_name', 'ilike', "%{$search}%")
                    ->orWhere('document', 'like', "%{$search}%");
            });
        }

        $clients = $query->orderBy('trade_name')
            ->orderBy('legal_name')
            ->get(['id', 'number', 'trade_name', 'legal_name', 'document', 'status']);

        return ClientLookupResource::collection($clients);
    }

    public function sellers(LookupRequest $request): AnonymousResourceCollection
    {
        $query = Seller::query();

        $status = $this->resolveStatusFilter($request, SellerStatus::allValues());
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('trade_name', 'ilike', "%{$search}%")
                    ->orWhere('legal_name', 'ilike', "%{$search}%");
            });
        }

        $sellers = $query->orderBy('trade_name')
            ->orderBy('legal_name')
            ->get(['id', 'number', 'trade_name', 'legal_name', 'status']);

        return SellerLookupResource::collection($sellers);
    }

    public function products(LookupRequest $request): AnonymousResourceCollection
    {
        $query = Product::query();

        $status = $this->resolveStatusFilter($request, ProductStatus::allValues());
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('name', 'ilike', "%{$search}%");
            });
        }

        $products = $query->orderBy('name')
            ->get(['id', 'number', 'name', 'unit', 'sale_price', 'cost_price', 'stock_quantity', 'status']);

        return ProductLookupResource::collection($products);
    }

    public function suppliers(LookupRequest $request): AnonymousResourceCollection
    {
        $query = Supplier::query();

        $status = $this->resolveStatusFilter($request, SupplierStatus::allValues());
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('trade_name', 'ilike', "%{$search}%")
                    ->orWhere('legal_name', 'ilike', "%{$search}%")
                    ->orWhere('document', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->orderBy('trade_name')
            ->orderBy('legal_name')
            ->get(['id', 'number', 'trade_name', 'legal_name', 'document', 'status']);

        return SupplierLookupResource::collection($suppliers);
    }

    public function brands(LookupRequest $request): AnonymousResourceCollection
    {
        $query = Brand::query();

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where('name', 'ilike', "%{$search}%");
        }

        $brands = $query->orderBy('name')->get(['id', 'number', 'name']);

        return BrandLookupResource::collection($brands);
    }

    public function categories(LookupRequest $request): AnonymousResourceCollection
    {
        $query = Category::query();

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where('name', 'ilike', "%{$search}%");
        }

        $categories = $query->orderBy('name')->get(['id', 'number', 'name']);

        return CategoryLookupResource::collection($categories);
    }

    public function clientByNumber(LookupByNumberRequest $request, string $number): ClientLookupResource
    {
        $parsedNumber = $this->parsePositiveNumber($number);
        $query = Client::query();

        $status = $this->resolveStatusFilter($request, ClientStatus::allValues());
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $client = $query->where('number', $parsedNumber)
            ->firstOrFail(['id', 'number', 'trade_name', 'legal_name', 'document', 'status']);

        return ClientLookupResource::make($client);
    }

    public function sellerByNumber(LookupByNumberRequest $request, string $number): SellerLookupResource
    {
        $parsedNumber = $this->parsePositiveNumber($number);
        $query = Seller::query();

        $status = $this->resolveStatusFilter($request, SellerStatus::allValues());
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $seller = $query->where('number', $parsedNumber)
            ->firstOrFail(['id', 'number', 'trade_name', 'legal_name', 'status']);

        return SellerLookupResource::make($seller);
    }

    public function productByNumber(LookupByNumberRequest $request, string $number): ProductLookupResource
    {
        $parsedNumber = $this->parsePositiveNumber($number);
        $query = Product::query();

        $status = $this->resolveStatusFilter($request, ProductStatus::allValues());
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $product = $query->where('number', $parsedNumber)
            ->firstOrFail(['id', 'number', 'name', 'unit', 'sale_price', 'cost_price', 'stock_quantity', 'status']);

        return ProductLookupResource::make($product);
    }

    public function supplierByNumber(LookupByNumberRequest $request, string $number): SupplierLookupResource
    {
        $parsedNumber = $this->parsePositiveNumber($number);
        $query = Supplier::query();

        $status = $this->resolveStatusFilter($request, SupplierStatus::allValues());
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $supplier = $query->where('number', $parsedNumber)
            ->firstOrFail(['id', 'number', 'trade_name', 'legal_name', 'document', 'status']);

        return SupplierLookupResource::make($supplier);
    }

    public function brandByNumber(string $number): BrandLookupResource
    {
        $parsedNumber = $this->parsePositiveNumber($number);

        $brand = Brand::query()
            ->where('number', $parsedNumber)
            ->firstOrFail(['id', 'number', 'name']);

        return BrandLookupResource::make($brand);
    }

    public function categoryByNumber(string $number): CategoryLookupResource
    {
        $parsedNumber = $this->parsePositiveNumber($number);

        $category = Category::query()
            ->where('number', $parsedNumber)
            ->firstOrFail(['id', 'number', 'name']);

        return CategoryLookupResource::make($category);
    }

    private function parsePositiveNumber(string $number): int
    {
        $parsed = filter_var($number, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if ($parsed === false) {
            throw new NotFoundHttpException(__('errors.resource_not_found'));
        }

        return $parsed;
    }

    /**
     * @param  array<int, string>  $allowedValues
     */
    private function resolveStatusFilter(Request $request, array $allowedValues): string
    {
        $status = $request->query('status', 'active');
        $allowed = ['all', ...$allowedValues];

        if (! \in_array($status, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => __('validation.enum', ['attribute' => 'status']),
            ]);
        }

        return $status;
    }
}
