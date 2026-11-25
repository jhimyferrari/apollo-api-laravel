<?php

namespace App\Http\Controllers\Api\V1;

use App\Enum\PermissionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\IndexProductRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ProductController extends Controller implements HasMiddleware
{
    public function __construct(
        protected ProductService $productService
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('abilities:'.PermissionType::PRODUCT_CREATE->value, only: ['store']),
            new Middleware('abilities:'.PermissionType::PRODUCT_READ->value, only: ['index', 'show']),
            new Middleware('abilities:'.PermissionType::PRODUCT_UPDATE->value, only: ['update']),
            new Middleware('abilities:'.PermissionType::PRODUCT_DELETE->value, only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(IndexProductRequest $request): AnonymousResourceCollection
    {
        $query = Product::query()->with(['brand', 'categories', 'ncm_code']);

        $status = $request->query('status');
        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        $brandId = $request->query('brand_id');
        if (is_string($brandId) && $brandId !== '') {
            if ($brandId === 'none') {
                $query->whereNull('brand_id');
            } else {
                $query->where('brand_id', $brandId);
            }
        }

        $categoryId = $request->query('category_id');
        if (is_string($categoryId) && $categoryId !== '') {
            $query->whereHas('categories', fn (Builder $q) => $q->where('categories.id', $categoryId));
        }

        $term = $request->searchTerm();
        if ($term !== null) {
            $number = $request->searchPositiveInt();
            if (str_starts_with($term, '#') && $number !== null) {
                $query->where('number', $number);
            } else {
                $pattern = $request->likePattern($term);
                $digitsOnly = preg_replace('/\D/', '', $term) ?? '';
                $digitsPattern = $digitsOnly !== '' ? $request->likePattern($digitsOnly) : null;

                $query->where(function (Builder $q) use ($pattern, $digitsPattern, $number): void {
                    $q->where('name', 'ilike', $pattern)
                        ->orWhere('ean', 'ilike', $pattern)
                        ->orWhereHas('brand', fn (Builder $brandQ) => $brandQ->where('name', 'ilike', $pattern))
                        ->orWhereHas('categories', fn (Builder $catQ) => $catQ->where('name', 'ilike', $pattern))
                        ->orWhereHas('ncm_code', function (Builder $ncmQ) use ($pattern, $digitsPattern): void {
                            $ncmQ->where('code', 'ilike', $pattern);
                            if ($digitsPattern !== null) {
                                $ncmQ->orWhere('code', 'like', $digitsPattern);
                            }
                        });

                    if ($number !== null) {
                        $q->orWhere('number', $number);
                    }
                });
            }
        }

        $request->applyOrderBy($query);

        return ProductResource::collection($query->paginate($request->perPage()));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $newProduct = $this->productService->create($request->validated(), Auth()->user());

        return $this->success($newProduct, __('messages.created', ['resource' => __('validation.resources.product')]), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product): ProductResource
    {
        return ProductResource::make($product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product): Response
    {
        $this->productService->update($product, $request->validated());

        return response()->noContent();

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product): Response
    {
        $this->productService->delete($product);

        return response()->noContent();
    }
}
