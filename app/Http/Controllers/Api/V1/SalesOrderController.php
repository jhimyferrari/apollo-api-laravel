<?php

namespace App\Http\Controllers\Api\V1;

use App\Enum\PermissionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\UpdateOrderPaymentStatusRequest;
use App\Http\Requests\SalesOrder\IndexSalesOrderRequest;
use App\Http\Requests\SalesOrder\StoreSalesOrderRequest;
use App\Http\Requests\SalesOrder\UpdateSalesOrderRequest;
use App\Http\Resources\SalesOrderResource;
use App\Models\Client;
use App\Models\SalesOrder;
use App\Models\Seller;
use App\Services\SalesOrderService;
use Illuminate\Database\Eloquent\Builder;
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
    public function index(IndexSalesOrderRequest $request): AnonymousResourceCollection
    {
        $query = SalesOrder::query()->with([
            'client',
            'seller',
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
                    $q->whereHas('client', fn (Builder $cq) => $cq->where('legal_name', 'ilike', $pattern)->orWhere('trade_name', 'ilike', $pattern))
                        ->orWhereHas('seller', fn (Builder $sq) => $sq->where('legal_name', 'ilike', $pattern)->orWhere('trade_name', 'ilike', $pattern));

                    if ($number !== null) {
                        $q->orWhere('number', $number);
                    }
                });
            }
        }

        $sortBy = $request->sortBy();
        $dir = $request->sortDirection();
        if ($sortBy === 'client') {
            $query->orderBy(
                Client::select('legal_name')->whereColumn('clients.id', 'sales_orders.client_id')->limit(1),
                $dir
            )->orderBy('id');
        } elseif ($sortBy === 'seller') {
            $query->orderBy(
                Seller::selectRaw("COALESCE(NULLIF(trade_name, ''), legal_name)")->whereColumn('sellers.id', 'sales_orders.seller_id')->limit(1),
                $dir
            )->orderBy('id');
        } else {
            $request->applyOrderBy($query);
        }

        return SalesOrderResource::collection($query->paginate($request->perPage()));
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
