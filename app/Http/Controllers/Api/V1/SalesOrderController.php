<?php

namespace App\Http\Controllers\Api\V1;

use App\Enum\PermissionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\UpdateOrderPaymentStatusRequest;
use App\Http\Requests\SalesOrder\StoreSalesOrderRequest;
use App\Http\Requests\SalesOrder\UpdateSalesOrderRequest;
use App\Http\Resources\SalesOrderResource;
use App\Models\SalesOrder;
use App\Services\SalesOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class SalesOrderController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly SalesOrderService $service,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('abilities:'.PermissionType::SALES_ORDER_CREATE->value, only: ['store']),
            new Middleware('abilities:'.PermissionType::SALES_ORDER_READ->value, only: ['index', 'show']),
            new Middleware('abilities:'.PermissionType::SALES_ORDER_UPDATE->value, only: ['update', 'cancel', 'confirm', 'reopen', 'updatePaymentStatus']),
            new Middleware('abilities:'.PermissionType::SALES_ORDER_DELETE->value, only: ['destroy']),

        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
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
    public function store(StoreSalesOrderRequest $request): JsonResponse
    {
        $newOrder = $this->service->create($request->validated(), Auth()->user());

        return $this->success($newOrder, __('messages.created', ['resource' => __('validation.resources.sales_order')]), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(SalesOrder $salesOrder): SalesOrderResource
    {
        $salesOrder->load(['client', 'seller', 'items.product']);

        return SalesOrderResource::make($salesOrder);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSalesOrderRequest $request, SalesOrder $salesOrder): Response
    {
        $this->service->update($salesOrder, $request->validated());

        return response()->noContent();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SalesOrder $salesOrder): Response
    {
        $this->service->delete($salesOrder);

        return response()->noContent();
    }

    public function confirm(SalesOrder $salesOrder): Response
    {
        $this->service->confirm($salesOrder);

        return response()->noContent();
    }

    public function cancel(Request $request, SalesOrder $salesOrder): Response
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);
        $this->service->cancel($salesOrder, $validated['reason'] ?? null);

        return response()->noContent();
    }

    public function reopen(Request $request, SalesOrder $salesOrder): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);
        $data = $this->service->recreateFrom($salesOrder, $validated['reason'] ?? null);

        return $this->success($data, __('messages.order_reopened_successfully'), 201);
    }

    public function updatePaymentStatus(UpdateOrderPaymentStatusRequest $request, SalesOrder $salesOrder): Response
    {
        $this->service->updatePaymentStatus($salesOrder, $request->validated('payment_status'));

        return response()->noContent();
    }
}
