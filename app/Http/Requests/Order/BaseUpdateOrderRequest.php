<?php

namespace App\Http\Requests\Order;

use App\Http\Requests\BaseUpdateRequest;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use Illuminate\Validation\Validator;

abstract class BaseUpdateOrderRequest extends BaseUpdateRequest
{
    public function authorize(): bool
    {
        return true;
    }

    abstract protected function resolveOrder(): SalesOrder|PurchaseOrder|null;

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            ...parent::after(),
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $validated = $validator->validated();
                if (! \array_key_exists('items', $validated)) {
                    return;
                }

                $items = $validated['items'];
                $toAdd = $items['to_add'] ?? [];
                $toUpdate = $items['to_update'] ?? [];
                $toRemove = $items['to_remove'] ?? [];

                if (empty($toAdd) && empty($toUpdate) && empty($toRemove)) {
                    $validator->errors()->add('items', __('validation.empty_update_payload'));

                    return;
                }

                foreach ($toUpdate as $index => $item) {
                    if (! \array_key_exists('product_id', $item) && ! \array_key_exists('number', $item) && ! \array_key_exists('quantity', $item)) {
                        $validator->errors()->add("items.to_update.{$index}", __('validation.empty_update_payload'));
                    }
                }

                $updateIds = array_column($toUpdate, 'id');
                if (! empty(array_intersect($updateIds, $toRemove))) {
                    $validator->errors()->add('items', __('validation.order.items_conflict'));
                }

                if (! empty($toRemove) && empty($toAdd)) {
                    $order = $this->resolveOrder();
                    if ($order !== null) {
                        $existingCount = $order->items()->count();
                        $removingCount = $order->items()->whereIn('id', $toRemove)->count();
                        if ($existingCount > 0 && ($existingCount - $removingCount) <= 0) {
                            $validator->errors()->add('items', __('validation.min.array', [
                                'attribute' => __('validation.attributes.items'),
                                'min' => 1,
                            ]));
                        }
                    }
                }
            },
        ];
    }
}
