<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\StoreSellerRequest;
use App\Http\Requests\Seller\UpdateSellerRequest;
use App\Http\Resources\SellerResource;
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
            new Middleware('abilities:seller.create', only: ['store']),
            new Middleware('abilities:seller.view', only: ['index', 'show']),
            new Middleware('abilities:seller.update', only: ['update']),
            new Middleware('abilities:seller.delete', only: ['destroy']),
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
        $newSeller = $this->sellerService->createWithOrganization($request->validated(), Auth()->user());

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
}
