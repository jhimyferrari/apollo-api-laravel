<?php

namespace App\Http\Controllers\Api\V1;

use App\Enum\PermissionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ProductController extends Controller implements HasMiddleware
{
    public function __construct(
        protected ProductService $productService
    ) {}

    public static function middleware()
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
    public function index()
    {
        return ProductResource::collection(Product::with(['brand', 'categories'])->paginate(15));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        $newProduct = $this->productService->create($request->validated(), Auth()->user());

        return $this->success($newProduct, 'Product created successfully.', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        return ProductResource::make($product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $this->productService->update($product, $request->validated());

        return response()->noContent();

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $this->productService->delete($product);

        return response()->noContent();
    }
}
