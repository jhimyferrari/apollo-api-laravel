<?php

namespace App\Models;

use App\Models\Scopes\OrganizationScope;
use App\Traits\HasSequencialNumber;
use App\Traits\ProtectsOrganization;
use Database\Factories\SellerFactory;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property int $number
 * @property string $status
 * @property string $document
 * @property string $legal_name
 * @property string $trade_name
 * @property string|null $state_registration
 * @property string|null $phone
 * @property string|null $email
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $organization_id
 * @property-read Organization $organization
 *
 * @method static \Database\Factories\SellerFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seller newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seller newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seller onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seller query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seller whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seller whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seller whereDocument($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seller whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seller whereEndedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seller whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seller whereLegalName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seller whereNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seller whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seller wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seller whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seller whereStateRegistration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seller whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seller whereTradeName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seller whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seller withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seller withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[ScopedBy([OrganizationScope::class])]
class Seller extends Model
{
    /** @use HasFactory<SellerFactory> */
    use HasFactory,HasSequencialNumber,HasUuids,ProtectsOrganization,SoftDeletes;

    protected $table = 'sellers';

    protected $primaryKey = 'id';

    public $timestamp = true;

    protected $fillable = [
        'document',
        'legal_name',
        'trade_name',
        'state_registration',
        'phone',
        'email',
        'started_at',
        'ended_at',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function casts(): array
    {
        return [
            'started_at' => 'date',
            'ended_at' => 'date',
        ];
    }
}
