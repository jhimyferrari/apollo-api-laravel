<?php

namespace App\Http\Controllers\Api\V1;

use App\Enum\PermissionType;
use App\Http\Controllers\Controller;
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
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

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
            ]), only: ['clients']),

            new Middleware('ability:'.implode(',', [
                PermissionType::SELLER_READ->value,
                PermissionType::SALES_ORDER_CREATE->value,
                PermissionType::SALES_ORDER_UPDATE->value,
                PermissionType::SALES_ORDER_READ->value,
            ]), only: ['sellers']),

            new Middleware('ability:'.implode(',', [
                PermissionType::PRODUCT_READ->value,
                PermissionType::SALES_ORDER_CREATE->value,
                PermissionType::SALES_ORDER_UPDATE->value,
                PermissionType::SALES_ORDER_READ->value,
                PermissionType::PURCHASE_ORDER_CREATE->value,
                PermissionType::PURCHASE_ORDER_UPDATE->value,
                PermissionType::PURCHASE_ORDER_READ->value,
            ]), only: ['products']),

            new Middleware('ability:'.implode(',', [
                PermissionType::SUPPLIER_READ->value,
                PermissionType::PURCHASE_ORDER_CREATE->value,
                PermissionType::PURCHASE_ORDER_UPDATE->value,
                PermissionType::PURCHASE_ORDER_READ->value,
            ]), only: ['suppliers']),

            new Middleware('ability:'.implode(',', [
                PermissionType::BRAND_READ->value,
                PermissionType::PRODUCT_CREATE->value,
                PermissionType::PRODUCT_UPDATE->value,
            ]), only: ['brands']),

            new Middleware('ability:'.implode(',', [
                PermissionType::CATEGORY_READ->value,
                PermissionType::PRODUCT_CREATE->value,
                PermissionType::PRODUCT_UPDATE->value,
            ]), only: ['categories']),
        ];
    }

    public function clients(LookupRequest $request): AnonymousResourceCollection
    {
        $query = Client::query();

        $status = $request->query('status', 'active');
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

        $status = $request->query('status', 'active');
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

        $status = $request->query('status', 'active');
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

        $status = $request->query('status', 'active');
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
}
