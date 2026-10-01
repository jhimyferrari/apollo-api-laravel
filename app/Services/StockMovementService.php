<?php

namespace App\Services;

use App\Enum\StockMovementType;
use App\Exceptions\StockException;
use App\Interfaces\Models\MovesStock;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockMovementService
{
    public function move(Product $product, float $quantity, StockMovementType $type, MovesStock $reference): StockMovement
    {
        return DB::transaction(function () use ($product, $quantity, $type, $reference) {
            /** @var Product $locked */
            $locked = Product::query()->where('id', $product->id)->withTrashed()->lockForUpdate()->firstOrFail();

            $newBalance = $type === StockMovementType::In
                ? $locked->stock_quantity + $quantity
                : $locked->stock_quantity - $quantity;

            if ($newBalance < 0) {
                throw StockException::insufficientStock($locked, $quantity);
            }

            $movement = $reference->stockMovements()->create([
                'organization_id' => $locked->organization_id,
                'product_id' => $locked->id,
                'type' => $type,
                'quantity' => $quantity,
                'balance_after' => $newBalance,
            ]
            );
            $locked->update(['stock_quantity' => $newBalance]);

            return $movement;
        });
    }
}
