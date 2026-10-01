<?php

namespace App\Http\Controllers\Api\V1;

use App\Enum\PermissionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\SalesOrder\StoreSalesOrderRequest;
use App\Http\Requests\SalesOrder\UpdateSalesOrderRequest;
use App\Http\Resources\SalesOrderResource;
use App\Models\SalesOrder;
use App\Services\SalesOrderService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class SalesOrderController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly SalesOrderService $service,
    ) {}

    public static function middleware()
    {
        return [
            new Middleware('abilities:'.PermissionType::SALES_ORDER_CREATE->value, only: ['store']),
            new Middleware('abilities:'.PermissionType::SALES_ORDER_READ->value, only: ['index', 'show']),
            new Middleware('abilities:'.PermissionType::SALES_ORDER_UPDATE->value, only: ['update', 'cancel', 'confirm', 'reopen']),
            new Middleware('abilities:'.PermissionType::SALES_ORDER_DELETE->value, only: ['destroy']),

        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return SalesOrderResource::collection(SalesOrder::with([
            'client',
            'seller',
            'items',
            'items.product',
        ])->paginate(15));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSalesOrderRequest $request)
    {
        $newOrder = $this->service->create($request->validated(), Auth()->user());

        return $this->success($newOrder, 'SalesOrder created sucessfully.', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(SalesOrder $salesOrder)
    {
        return SalesOrderResource::make($salesOrder);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSalesOrderRequest $request, SalesOrder $salesOrder)
    {
        $this->service->update($salesOrder, $request->validated());

        return response()->noContent();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SalesOrder $salesOrder)
    {
        $this->service->delete($salesOrder);

        return response()->noContent();
    }

    public function confirm(SalesOrder $salesOrder)
    {
        $this->service->confirm($salesOrder);

        return response()->noContent();
    }

    public function cancel(SalesOrder $salesOrder)
    {
        $this->service->cancel($salesOrder);

        return response()->noContent();
    }

    public function reopen(Request $request, SalesOrder $salesOrder)
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string'],
        ]);
        $data = $this->service->recreateFrom($salesOrder, $validated['reason'] ?? null);

        return $this->success($data, 'SalesOrder reopened sucessfully', 201);
    }
}
