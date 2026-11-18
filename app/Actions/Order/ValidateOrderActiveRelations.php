<?php

namespace App\Actions\Order;

use App\Actions\Validation\ValidateResourceStatus;
use App\Exceptions\ModelIsDeletedException;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ValidateOrderActiveRelations
{
    public function __construct(
        private readonly ValidateResourceStatus $validateResourceStatus,
    ) {}

    /**
     * Validate that the order's linked partner(s) and item products exist (not soft-deleted) and are active.
     */
    public function execute(SalesOrder|PurchaseOrder $order, Collection $items, bool $requireItems = false): void
    {
        if ($requireItems && $items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => __('validation.required', ['attribute' => __('validation.attributes.items')]),
            ]);
        }

        if ($order instanceof SalesOrder) {
            $client = $order->client()->withoutGlobalScope(OrganizationScope::class)->withTrashed()->first();
            if ($client === null || $client->deleted_at !== null) {
                throw ValidationException::withMessages([
                    'client_id' => __('validation.client', ['attribute' => __('validation.attributes.client_id')]),
                ]);
            }
            $this->validateResourceStatus->execute($client, 'client_id');

            $seller = $order->seller()->withoutGlobalScope(OrganizationScope::class)->withTrashed()->first();
            if ($seller === null || $seller->deleted_at !== null) {
                throw ValidationException::withMessages([
                    'seller_id' => __('validation.seller', ['attribute' => __('validation.attributes.seller_id')]),
                ]);
            }
            $this->validateResourceStatus->execute($seller, 'seller_id');
        }

        if ($order instanceof PurchaseOrder) {
            $supplier = $order->supplier()->withoutGlobalScope(OrganizationScope::class)->withTrashed()->first();
            if ($supplier === null || $supplier->deleted_at !== null) {
                throw ValidationException::withMessages([
                    'supplier_id' => __('validation.supplier', ['attribute' => __('validation.attributes.supplier_id')]),
                ]);
            }
            $this->validateResourceStatus->execute($supplier, 'supplier_id');
        }

        foreach ($items as $item) {
            if ($item->product === null || $item->product->deleted_at !== null) {
                throw ModelIsDeletedException::forProduct($item->product_id);
            }
            $this->validateResourceStatus->execute($item->product, 'product_id');
        }
    }
}
