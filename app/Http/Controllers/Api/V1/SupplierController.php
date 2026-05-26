<?php

namespace App\Http\Controllers\Api\V1;

use App\Enum\PermissionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use App\Services\SupplierService;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class SupplierController extends Controller implements HasMiddleware
{
    public function __construct(
        protected SupplierService $supplierService) {}

    public static function middleware()
    {
        return [
            new Middleware('abilities:'.PermissionType::SUPPLIER_CREATE->value, only: ['store']),
            new Middleware('abilities:'.PermissionType::SUPPLIER_READ->value, only: ['index', 'show']),
            new Middleware('abilities:'.PermissionType::SUPPLIER_UPDATE->value, only: ['update']),
            new Middleware(['abilities:'.PermissionType::SUPPLIER_DELETE->value], only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return SupplierResource::collection(Supplier::with('addresses')->paginate(15));

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSupplierRequest $request)
    {
        $newSupplier = $this->supplierService->create($request->validated(), Auth()->user());

        return $this->success($newSupplier, 'Supplier created succesfully.', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier)
    {
        return SupplierResource::make($supplier);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {

        $this->supplierService->update($supplier, $request->validated());

        return response()->noContent();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier)
    {
        $this->supplierService->delete($supplier);

        return response()->noContent();
    }
}
