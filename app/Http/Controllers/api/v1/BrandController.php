<?php

namespace App\Http\Controllers\api\v1;

use App\Enum\PermissionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Brand\StoreBrandRequest;
use App\Http\Requests\Brand\UpdateBrandRequest;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use App\Services\BrandService;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class BrandController extends Controller implements HasMiddleware
{
    public function __construct(
        protected BrandService $brandService
    ) {}

    public static function middleware()
    {
        return [

            new Middleware('abilities:'.PermissionType::BRAND_CREATE->value, only: ['store']),
            new Middleware('abilities:'.PermissionType::BRAND_READ->value, only: ['index', 'show']),
            new Middleware('abilities:'.PermissionType::BRAND_UPDATE->value, only: ['update']),
            new Middleware(['abilities:'.PermissionType::BRAND_DELETE->value], only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        return BrandResource::collection(Brand::paginate(15));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBrandRequest $request)
    {
        $newBrand = $this->brandService->createWithOrganization($request->validated(), Auth()->user());

        return $this->success($newBrand, 'Brand created successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Brand $brand)
    {
        return BrandResource::make($brand);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBrandRequest $request, Brand $brand)
    {
        $this->brandService->update($brand, $request->validated());

        return response()->noContent();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Brand $brand)
    {
        $this->brandService->delete($brand);

        return response()->noContent();
    }
}
