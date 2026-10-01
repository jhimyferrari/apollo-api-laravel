<?php

namespace App\Models;

use App\Casts\AsMoney;
use App\Enum\Status\OrderStatus;
use App\Enum\Status\PaymentStatus;
use App\Interfaces\Models\HasStatus;
use App\Interfaces\Models\MovesStock;
use App\Models\Scopes\OrganizationScope;
use App\Traits\HasSequencialNumber;
use App\Traits\ProtectsOrganization;
use App\ValueObjects\Money;
use Database\Factories\PurchaseOrderFactory;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ScopedBy(OrganizationScope::class)]
class PurchaseOrder extends Model implements HasStatus, MovesStock
{
    /** @use HasFactory<PurchaseOrderFactory> */
    use HasFactory,HasSequencialNumber, HasUuids, ProtectsOrganization,SoftDeletes;

    protected $table = 'purchase_orders';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'total' => AsMoney::class,
            'payment_status' => PaymentStatus::class,
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function statusEnumClass(): string
    {
        return OrderStatus::class;
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function stockMovementItems(): Collection
    {
        return $this->items()->with('product')->get();
    }

    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    public function sumTotalByItems()
    {
        $total = Money::zero();
        foreach ($this->items as $item) {
            $total = $total->add($item->subtotal);
        }

        return $total;
    }

    public function replacedOrder(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaces_order_id');
    }

    public function replacedBy(): HasOne
    {
        return $this->hasOne(self::class, 'replaces_order_id');
    }
}
