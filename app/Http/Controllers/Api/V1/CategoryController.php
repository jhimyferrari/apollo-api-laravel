<?php

namespace App\Http\Controllers\Api\V1;

use App\Enum\PermissionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Brand\StoreBrandRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CategoryController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('abilities:'.PermissionType::CATEGORY_CREATE->value, only: ['store']),
            new Middleware('abilities:'.PermissionType::CATEGORY_READ->value, only: ['index', 'show']),
            new Middleware('abilities:'.PermissionType::CATEGORY_UPDATE->value, only: ['update']),
            new Middleware('abilities:'.PermissionType::CATEGORY_DELETE->value, only: ['destroy']),
        ];
    }

    public function __construct(
        protected CategoryService $categoryService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return CategoryResource::collection(Category::paginate(15));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBrandRequest $request)
    {
        $newCategory = $this->categoryService->create($request->validated(), Auth()->user());

        return $this->success($newCategory, 'Category created successfully.', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        return CategoryResource::make($category);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $this->categoryService->update($category, $request->validated());

        return response()->noContent();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        $this->categoryService->delete($category);

        return response()->noContent();
    }
}
