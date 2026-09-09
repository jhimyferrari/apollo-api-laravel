<?php

namespace App\Http\Controllers\Api\V1;

use App\Enum\PermissionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Address\StoreAddressRequest;
use App\Http\Requests\Seller\StoreSellerRequest;
use App\Http\Requests\Seller\UpdateSellerRequest;
use App\Http\Resources\SellerResource;
use App\Models\Address;
use App\Models\Seller;
use App\Services\SellerService;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class SellerController extends Controller implements HasMiddleware
{
    public function __construct(
        protected SellerService $sellerService
    ) {}

    public static function middleware()
    {
        return [
            new Middleware('abilities:'.PermissionType::SELLER_CREATE->value, only: ['store']),
            new Middleware('abilities:'.PermissionType::SELLER_READ->value, only: ['index', 'show']),
            new Middleware('abilities:'.PermissionType::SELLER_UPDATE->value, only: ['update']),
            new Middleware('abilities:'.PermissionType::SELLER_DELETE->value, only: ['destroy']),
            new Middleware(['ability:'.PermissionType::SELLER_CREATE->value.','.PermissionType::SELLER_UPDATE->value], only: ['storeAddress', 'setDefaultAddress']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return SellerResource::collection(Seller::paginate(15));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSellerRequest $request)
    {
        $newSeller = $this->sellerService->create($request->validated(), Auth()->user());

        return $this->success($newSeller, 'Seller created sucessfully.', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Seller $seller)
    {
        return SellerResource::make($seller);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSellerRequest $request, Seller $seller)
    {
        $this->sellerService->update($seller, $request->validated());

        return response()->noContent();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Seller $seller)
    {
        $this->sellerService->delete($seller);

        return response()->noContent();
    }

    public function storeAddress(StoreAddressRequest $request, Seller $seller)
    {
        $newAddress = $this->sellerService->createAddress($seller, $request->validated());

        return $this->success($newAddress, 'Address created successfully.', 201);
    }

    public function setDefaultAddress(Seller $seller, Address $address)
    {
        $this->sellerService->setDefaultAddress($seller, $address);

        return response()->noContent();
    }
}
