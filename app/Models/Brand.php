<?php

namespace App\Models;

use App\Models\Scopes\OrganizationScope;
use App\Traits\HasSequencialNumber;
use App\Traits\ProtectsOrganization;
use Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ScopedBy(OrganizationScope::class)]
class Brand extends Model
{
    /** @use HasFactory<BrandFactory> */
    use HasFactory,HasSequencialNumber,HasUuids,ProtectsOrganization,SoftDeletes;

    protected $table = 'brands';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    protected $fillable = [
        'name',
        'description',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
