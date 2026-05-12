<?php

namespace App\Models;

use App\Models\Scopes\OrganizationScope;
use App\Traits\HasAddresses;
use App\Traits\HasSequencialNumber;
use App\Traits\ProtectsOrganization;
use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ScopedBy([OrganizationScope::class])]
class Supplier extends Model
{
    /** @use HasFactory<SupplierFactory> */
    use HasAddresses,HasFactory,HasSequencialNumber,HasUuids,ProtectsOrganization,SoftDeletes;

    protected $table = 'suppliers';

    protected $primaryKey = 'id';

    public $timestamp = true;

    protected $fillable = [
        'document',
        'legal_name',
        'trade_name',
        'state_registration',
        'phone',
        'email',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
