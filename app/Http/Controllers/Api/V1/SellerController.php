<?php

namespace App\Http\Controllers\Api\V1;

use App\Enum\PermissionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Address\StoreAddressRequest;
use App\Http\Requests\Seller\IndexSellerRequest;
use App\Http\Requests\Seller\StoreSellerRequest;
use App\Http\Requests\Seller\UpdateSellerRequest;
use App\Http\Resources\AddressResource;
use App\Http\Resources\SellerResource;
use App\Models\Address;
use App\Models\Seller;
use App\Services\AddressService;
use App\Services\SellerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class SellerController extends Controller implements HasMiddleware
{
    public function __construct(
        protected SellerService $sellerService,
        protected AddressService $addressService
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('abilities:'.PermissionType::SELLER_CREATE->value, only: ['store']),
            new Middleware('abilities:'.PermissionType::SELLER_READ->value, only: ['index', 'show', 'showAddresses']),
            new Middleware('abilities:'.PermissionType::SELLER_UPDATE->value, only: ['update']),
            new Middleware('abilities:'.PermissionType::SELLER_DELETE->value, only: ['destroy']),
            new Middleware(['ability:'.PermissionType::SELLER_CREATE->value.','.PermissionType::SELLER_UPDATE->value], only: ['storeAddress', 'setDefaultAddress']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(IndexSellerRequest $request): AnonymousResourceCollection
    {
        $query = Seller::query();
        $request->applyPartnerIndex($query);

        return SellerResource::collection($query->paginate($request->perPage()));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSellerRequest $request): JsonResponse
    {
        $newSeller = $this->sellerService->create($request->validated(), Auth()->user());

        return $this->success($newSeller, __('messages.created', ['resource' => __('validation.resources.seller')]), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Seller $seller): SellerResource
    {
        return SellerResource::make($seller);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSellerRequest $request, Seller $seller): Response
    {
        $this->sellerService->update($seller, $request->validated());

        return response()->noContent();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Seller $seller): Response
    {
        $this->sellerService->delete($seller);

        return response()->noContent();
    }

    public function storeAddress(StoreAddressRequest $request, Seller $seller): JsonResponse
    {
        $newAddress = $this->addressService->create($seller, $request->validated());

        return $this->success($newAddress, __('messages.created', ['resource' => __('validation.resources.address')]), 201);
    }

    public function setDefaultAddress(Seller $seller, Address $address): Response
    {
        $this->addressService->setDefault($seller, $address);

        return response()->noContent();
    }

    public function showAddresses(Seller $seller): AnonymousResourceCollection
    {
        return AddressResource::collection($seller->addresses()->with('city.uf')->paginate(5));
    }
}
