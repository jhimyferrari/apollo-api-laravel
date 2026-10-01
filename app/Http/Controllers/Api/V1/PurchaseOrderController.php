<?php

namespace App\Http\Controllers\Api\V1;

use App\Enum\PermissionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrder\StorePurchaseOrderRequest;
use App\Http\Requests\PurchaseOrder\UpdatePurchaseOrderRequest;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PurchaseOrderController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly PurchaseOrderService $service
    ) {}

    public static function middleware()
    {

        return [
            new Middleware('abilities:'.PermissionType::PURCHASE_ORDER_CREATE->value, only: ['store']),
            new Middleware('abilities:'.PermissionType::PURCHASE_ORDER_READ->value, only: ['index', 'show']),
            new Middleware('abilities:'.PermissionType::PURCHASE_ORDER_UPDATE->value, only: ['update', 'cancel', 'confirm', 'reopen']),
            new Middleware('abilities:'.PermissionType::PURCHASE_ORDER_DELETE->value, only: ['destroy']),

        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return PurchaseOrderResource::collection(PurchaseOrder::with([
            'supplier',
            'items',
            'items.product',
        ])->paginate(15));

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePurchaseOrderRequest $request)
    {
        $newOrder = $this->service->create($request->validated(), Auth()->user());

        return $this->success($newOrder, 'PurchaseOrder created sucessfully.', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(PurchaseOrder $purchaseOrder)
    {
        return PurchaseOrderResource::make($purchaseOrder);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder)
    {
        $this->service->update($purchaseOrder, $request->validated());

        return response()->noContent();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PurchaseOrder $purchaseOrder)
    {
        $this->service->delete($purchaseOrder);

        return response()->noContent();
    }

    public function confirm(PurchaseOrder $purchaseOrder)
    {
        $this->service->confirm($purchaseOrder);

        return response()->noContent();
    }

    public function cancel(PurchaseOrder $purchaseOrder)
    {
        $this->service->cancel($purchaseOrder);

        return response()->noContent();
    }

    public function reopen(Request $request, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string'],
        ]);
        $data = $this->service->recreateFrom($purchaseOrder, $validated['reason'] ?? null);

        return $this->success($data, 'PurchaseOrder reopened sucessfully', 201);
    }
}
