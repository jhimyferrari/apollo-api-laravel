<?php

namespace App\Models;

use App\Casts\AsMoney;
use App\Models\Scopes\OrganizationScope;
use App\Traits\ProtectsOrganization;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ScopedBy(OrganizationScope::class)]
class SalesOrderItem extends Model
{
    use HasUuids,ProtectsOrganization,SoftDeletes;

    protected $table = 'sales_order_items';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    protected $fillable = ['organization_id', 'number', 'product_id', 'quantity', 'unit_price', 'subtotal'];

    protected $casts =
        [
            'unit_price' => AsMoney::class,
            'subtotal' => AsMoney::class,
            'quantity' => 'decimal:3',
        ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
