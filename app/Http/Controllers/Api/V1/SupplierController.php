<?php

namespace App\Http\Controllers\Api\V1;

use App\Enum\PermissionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Address\StoreAddressRequest;
use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Http\Resources\AddressResource;
use App\Http\Resources\SupplierResource;
use App\Models\Address;
use App\Models\Supplier;
use App\Services\AddressService;
use App\Services\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class SupplierController extends Controller implements HasMiddleware
{
    public function __construct(
        protected SupplierService $supplierService,
        protected AddressService $addressService
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('abilities:'.PermissionType::SUPPLIER_CREATE->value, only: ['store']),
            new Middleware('abilities:'.PermissionType::SUPPLIER_READ->value, only: ['index', 'show', 'showAddresses']),
            new Middleware('abilities:'.PermissionType::SUPPLIER_UPDATE->value, only: ['update']),
            new Middleware(['abilities:'.PermissionType::SUPPLIER_DELETE->value], only: ['destroy']),
            new Middleware(['ability:'.PermissionType::SUPPLIER_CREATE->value.','.PermissionType::SUPPLIER_UPDATE->value], only: ['storeAddress', 'setDefaultAddress']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        return SupplierResource::collection(Supplier::with('addresses')->paginate(15));

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $newSupplier = $this->supplierService->create($request->validated(), Auth()->user());

        return $this->success($newSupplier, __('messages.created', ['resource' => __('validation.resources.supplier')]), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier): SupplierResource
    {
        return SupplierResource::make($supplier);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSupplierRequest $request, Supplier $supplier): Response
    {

        $this->supplierService->update($supplier, $request->validated());

        return response()->noContent();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier): Response
    {
        $this->supplierService->delete($supplier);

        return response()->noContent();
    }

    public function storeAddress(StoreAddressRequest $request, Supplier $supplier): JsonResponse
    {
        $newAddress = $this->addressService->create($supplier, $request->validated());

        return $this->success($newAddress, __('messages.created', ['resource' => __('validation.resources.address')]), 201);
    }

    public function setDefaultAddress(Supplier $supplier, Address $address): Response
    {
        $this->addressService->setDefault($supplier, $address);

        return response()->noContent();
    }

    public function showAddresses(Supplier $supplier): AnonymousResourceCollection
    {
        return AddressResource::collection($supplier->addresses()->with('city.uf')->paginate(5));
    }
}
