<?php

namespace App\Models;

use App\Enum\StockMovementType;
use App\Models\Scopes\OrganizationScope;
use App\Traits\ProtectsOrganization;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[ScopedBy(OrganizationScope::class)]
class StockMovement extends Model
{
    use HasUuids,ProtectsOrganization;

    protected $table = 'stock_movements';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [

        'organization_id',
        'product_id',
        'type',
        'quantity',
        'balance_after',
    ];

    protected function casts(): array
    {
        return [
            'type' => StockMovementType::class,
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $m) => $m->created_at ??= now());
        static::updating(fn () => throw new \LogicException('StockMovement it`s imutable.'));
        static::deleting(fn () => throw new \LogicException('StockMovement can not be removed.'));
    }

    public function product(): BelongsTo
    {

        return $this->belongsTo(Product::class);
    }

    public function reference(): MorphTo
    {

        return $this->morphTo();
    }

    public function organization(): BelongsTo
    {

        return $this->belongsTo(Organization::class);
    }
}
