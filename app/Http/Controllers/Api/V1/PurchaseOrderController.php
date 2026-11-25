<?php

namespace App\Http\Controllers\Api\V1;

use App\Enum\PermissionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\UpdateOrderPaymentStatusRequest;
use App\Http\Requests\PurchaseOrder\IndexPurchaseOrderRequest;
use App\Http\Requests\PurchaseOrder\StorePurchaseOrderRequest;
use App\Http\Requests\PurchaseOrder\UpdatePurchaseOrderRequest;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\PurchaseOrderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PurchaseOrderController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly PurchaseOrderService $service
    ) {}

    public static function middleware(): array
    {

        return [
            new Middleware('abilities:'.PermissionType::PURCHASE_ORDER_CREATE->value, only: ['store']),
            new Middleware('abilities:'.PermissionType::PURCHASE_ORDER_READ->value, only: ['index', 'show']),
            new Middleware('abilities:'.PermissionType::PURCHASE_ORDER_UPDATE->value, only: ['update', 'cancel', 'confirm', 'reopen', 'updatePaymentStatus']),
            new Middleware('abilities:'.PermissionType::PURCHASE_ORDER_DELETE->value, only: ['destroy']),

        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(IndexPurchaseOrderRequest $request): AnonymousResourceCollection
    {
        $query = PurchaseOrder::query()->with([
            'supplier',
            'items',
            'items.product',
        ]);

        $status = $request->query('status');
        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        $paymentStatus = $request->query('payment_status');
        if (is_string($paymentStatus) && $paymentStatus !== '') {
            $query->where('payment_status', $paymentStatus);
        }

        $term = $request->searchTerm();
        if ($term !== null) {
            $number = $request->searchPositiveInt();
            if (str_starts_with($term, '#') && $number !== null) {
                $query->where('number', $number);
            } else {
                $pattern = $request->likePattern($term);

                $query->where(function (Builder $q) use ($pattern, $number): void {
                    $q->whereHas('supplier', fn (Builder $sq) => $sq->where('legal_name', 'ilike', $pattern)->orWhere('trade_name', 'ilike', $pattern));

                    if ($number !== null) {
                        $q->orWhere('number', $number);
                    }
                });
            }
        }

        $sortBy = $request->sortBy();
        $dir = $request->sortDirection();
        if ($sortBy === 'supplier') {
            $query->orderBy(
                Supplier::select('legal_name')->whereColumn('suppliers.id', 'purchase_orders.supplier_id')->limit(1),
                $dir
            )->orderBy('id');
        } else {
            $request->applyOrderBy($query);
        }

        return PurchaseOrderResource::collection($query->paginate($request->perPage()));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePurchaseOrderRequest $request): JsonResponse
    {
        $newOrder = $this->service->create($request->validated(), Auth()->user());

        return $this->success($newOrder, __('messages.created', ['resource' => __('validation.resources.purchase_order')]), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(PurchaseOrder $purchaseOrder): PurchaseOrderResource
    {
        return PurchaseOrderResource::make($purchaseOrder);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): Response
    {
        $this->service->update($purchaseOrder, $request->validated());

        return response()->noContent();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PurchaseOrder $purchaseOrder): Response
    {
        $this->service->delete($purchaseOrder);

        return response()->noContent();
    }

    public function confirm(PurchaseOrder $purchaseOrder): Response
    {
        $this->service->confirm($purchaseOrder);

        return response()->noContent();
    }

    public function cancel(Request $request, PurchaseOrder $purchaseOrder): Response
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);
        $this->service->cancel($purchaseOrder, $validated['reason'] ?? null);

        return response()->noContent();
    }

    public function reopen(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);
        $data = $this->service->recreateFrom($purchaseOrder, $validated['reason'] ?? null);

        return $this->success($data, 'PurchaseOrder reopened sucessfully', 201);
    }

    public function updatePaymentStatus(UpdateOrderPaymentStatusRequest $request, PurchaseOrder $purchaseOrder): Response
    {
        $this->service->updatePaymentStatus($purchaseOrder, $request->validated('payment_status'));

        return response()->noContent();
    }
}
