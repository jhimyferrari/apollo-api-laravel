<?php

namespace App\Services;

use App\Actions\Order\CancelOrder;
use App\Actions\Order\ConfirmOrder;
use App\Actions\Validation\ValidateStatusEnum;
use App\Enum\Status\OrderStatus;
use App\Enum\Status\PaymentStatus;
use App\Enum\StockMovementType;
use App\Exceptions\InvalidStatusException;
use App\Exceptions\InvalidStatusTransitionException;
use App\Exceptions\ModelIsDeletedException;
use App\Exceptions\OrderCannotBeDeletedException;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Services\TreatmentService\Strategies\TreatArray;
use App\Services\TreatmentService\Strategies\TreatNumber;
use App\Services\TreatmentService\Strategies\TreatProduct;
use App\Services\TreatmentService\Strategies\TreatPurchaseOrder;
use App\Services\TreatmentService\Strategies\TreatPurchaseOrderItem;
use App\Services\TreatmentService\Strategies\TreatSupplier;
use App\Services\TreatmentService\TreatmentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService extends BaseService
{
    public function __construct(
        private readonly TreatmentService $treatment,
        private readonly ValidateStatusEnum $validateStatus,
        private readonly TreatProduct $treatProduct,
        private readonly TreatPurchaseOrder $treatPurchaseOrder,
        private readonly TreatSupplier $treatSupplier,
        private readonly TreatArray $treatArray,
        private readonly TreatNumber $treatNumber,
        private readonly ConfirmOrder $confirmOrder,
        private readonly CancelOrder $cancelOrder,
        private readonly TreatPurchaseOrderItem $treatPurchaseOrderItem,
    ) {
        parent::__construct(new PurchaseOrder);

    }

    public function create(array $data, User $user): PurchaseOrder
    {
        $order = new PurchaseOrder;
        $order->organization_id = $user->organization_id;
        $order->supplier_id = $this->treatment->for($this->treatSupplier, $data['supplier_id'], 'supplier_id', $this->model)->mustBeNotNull()->handle()->id;

        $order->status = OrderStatus::Draft;
        $order->payment_status = PaymentStatus::Pending;

        $this->treatment->for($this->treatArray->notEmpty(), $data['items'], 'items', $this->model)->mustBeNotNull()->handle();

        return DB::transaction(function () use ($order, $data) {
            $order->save();

            $this->createItems($order, $data['items']);

            $order->total = $order->sumTotalByItems();
            $order->save();

            return $order->refresh();
        });
    }

    public function update(Model $order, array $data): PurchaseOrder
    {
        if ($order->status !== OrderStatus::Draft) {
            throw new InvalidStatusException("Cannot update a order on status `{$order->status}`");
        }

        if (\array_key_exists('supplier_id', $data)) {
            $order->supplier_id = $this->treatment->for($this->treatSupplier, $data['supplier_id'], 'supplier_id', $this->model)->mustBeNotNull()->handle()->id;
        }

        if (\array_key_exists('payment_status', $data)) {
            $status = PaymentStatus::tryFrom($data['payment_status']);
            if ($status == null) {
                throw new InvalidStatusException("`{$data['payment_status']}` status doesn`t exist");
            }
            $order->payment_status = $status;
        }

        return DB::transaction(function () use ($order, $data) {

            if (\array_key_exists('items', $data)) {
                $items = $data['items'];

                // removing items
                if (\array_key_exists('to_remove', $items)) {
                    foreach ($items['to_remove'] as $itemToRemoved) {
                        $item = $this->treatment->for($this->treatPurchaseOrderItem->order($order), $itemToRemoved, 'item_id', $this->model)->handle();
                        $item->number = 0;
                        $item->delete();
                    }
                }
                // updating items
                if (\array_key_exists('to_update', $items)) {
                    $this->updateItems($order, $items['to_update']);
                }

                // adding items
                if (\array_key_exists('to_add', $items)) {
                    $this->createItems($order, $items['to_add']);
                }
                $order->total = $order->sumTotalByItems();
            }

            $order->save();

            return $order->refresh();
        });

    }

    public function recreateFrom(PurchaseOrder $previousOrder, ?string $reason = null): array
    {
        if ($previousOrder->replacedBy()->exists()) {
            throw new InvalidStatusTransitionException('Purchase Order already has been replaced');
        }
        if ($previousOrder->status === OrderStatus::Draft) {
            throw new InvalidStatusTransitionException('Purchase Order is already open');
        }

        return DB::transaction(function () use ($previousOrder, $reason) {
            if ($previousOrder->status !== OrderStatus::Cancelled) {
                $this->cancel($previousOrder, $reason);
            }
            $newPurchaseOrder = new PurchaseOrder;
            $newPurchaseOrder->organization_id = $previousOrder->organization_id;
            $newPurchaseOrder->supplier_id = $previousOrder->supplier_id;
            $newPurchaseOrder->replaces_order_id = $previousOrder->id;
            $newPurchaseOrder->total = $previousOrder->total;

            $newPurchaseOrder->save();

            $previousOrder->loadMissing('items.product');
            $outdatedPrices = [];

            foreach ($previousOrder->items as $item) {
                if ($item->product->deleted_at !== null) {
                    throw ModelIsDeletedException::forProduct('PurchaseOrder', $item->product_id);
                }
                $newPurchaseOrder->items()->create(
                    [
                        'organization_id' => $item->organization_id,
                        'product_id' => $item->product_id,
                        'number' => $item->number,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'subtotal' => $item->subtotal,
                    ]
                );

                if (! $item->product->sale_price->equals($item->unit_price)) {
                    $outdatedPrices[] = [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name,
                        'negotiated_price' => $item->unit_price->toStorageString(),
                        'current_price' => $item->product->sale_price->toStorageString(),
                    ];
                }
            }

            return [
                'order' => $newPurchaseOrder->fresh('items'),
                'outdated_prices' => $outdatedPrices,
            ];
        });
    }

    public function delete(Model $model): void
    {
        if ($model->status === OrderStatus::Draft) {
            DB::transaction(function () use ($model) {
                $model->delete();
                $model->items()->delete();
            });
        } else {
            throw OrderCannotBeDeletedException::notDraft($model->status);
        }
    }

    public function cancel(PurchaseOrder $order, ?string $reason = null): void
    {

        if ($order->status === OrderStatus::Confirmed || $order->status === OrderStatus::Draft) {
            $this->cancelOrder->execute($order, StockMovementType::In, $reason);
        } else {
            throw InvalidStatusTransitionException::make($order->status, OrderStatus::Cancelled);
        }

    }

    public function confirm(PurchaseOrder $order): void
    {
        if ($order->status === OrderStatus::Draft) {
            $this->confirmOrder->execute($order, StockMovementType::Out);
        } else {
            throw InvalidStatusTransitionException::make($order->status, OrderStatus::Confirmed);
        }
    }

    private function createItems(PurchaseOrder $order, array $items)
    {
        foreach ($items as $item) {
            /** @var Product $product */
            $product = $this->treatment->for($this->treatProduct, $item['product_id'], 'product_id', $this->model)->mustBeNotNull()->handle();

            $quantity = $this->treatment->for($this->treatNumber->positive(), $item['quantity'], 'quantity', $this->model)->mustBeNotNull()->handle();
            $subtotal = $product->sale_price->multiply($quantity);

            $order->items()->create([
                'organization_id' => $order->organization_id,
                'product_id' => $product->id,
                'number' => $this->treatment->for($this->treatNumber->positive()->asInteger(), $item['number'], 'number', $this->model)->mustBeNotNull()->handle(),
                'quantity' => $quantity,
                'unit_price' => $product->sale_price,
                'subtotal' => $subtotal,
            ]);
        }
    }

    private function updateItems(PurchaseOrder $order, array $items)
    {
        foreach ($items as $itemRaw) {
            $item = $this->treatment->for($this->treatPurchaseOrderItem->order($order), $itemRaw['id'], 'item_id', $this->model)->mustBeNotNull()->handle();

            if (\array_key_exists('product_id', $itemRaw)) {
                $product = $this->treatment->for($this->treatProduct, $itemRaw['product_id'], 'product_id', $this->model)->mustBeNotNull()->handle();
                $item->product_id = $product->id;
                $item->unit_price = $product->unit_price;
            }
            if (\array_key_exists('quantity', $itemRaw)) {
                $item->quantity = $this->treatment->for($this->treatNumber->positive(), $itemRaw['quantity'], 'quantity', $this->model)->mustBeNotNull()->handle();
            }
            if (\array_key_exists('number', $itemRaw)) {
                $item->number = $this->treatment->for($this->treatNumber->positive()->asInteger(), $itemRaw['number'], 'number', $this->model)->mustBeNotNull()->handle();
            }
            if ($item->isDirty('quantity') || $item->isDirty('unit_price')) {
                $item->subtotal = $item->unit_price->multiply($item->quantity);
            }
            $item->save();
        }
    }
}
