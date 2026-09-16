<?php

namespace App\Models;

use App\Enum\Status\ClientStatus;
use App\Helpers\DocumentHelper;
use App\Interfaces\HasStatus;
use App\Interfaces\Models\Addressable;
use App\Models\Scopes\OrganizationScope;
use App\Traits\HasAddresses;
use App\Traits\HasSequencialNumber;
use App\Traits\ProtectsOrganization;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

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
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $organization_id
 * @property-read Collection<int, Address> $addresses
 * @property-read int|null $addresses_count
 * @property-read Collection<int, Address> $defaultAddress
 * @property-read int|null $default_address_count
 * @property-read Organization $organization
 *
 * @method static \Database\Factories\ClientFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereDocument($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereLegalName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereStateRegistration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereTradeName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Client withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[ScopedBy([OrganizationScope::class])]
class Client extends Model implements Addressable, HasStatus
{
    /** @use HasFactory<ClientFactory> */
    use HasAddresses, HasFactory, HasSequencialNumber,HasUuids,ProtectsOrganization,SoftDeletes;

    protected $table = 'clients';

    protected $primaryKey = 'id';

    public $timestamp = true;

    protected $fillable = [
        'document',
        'legal_name',
        'trade_name',
        'state_registration',
        'phone',
        'email',
        'address_id',
    ];

    public function statusEnumClass(): string
    {
        return ClientStatus::class;
    }

    public function document(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                $formatedValue = DocumentHelper::remove_pontuation($value);

                if (\strlen($formatedValue) === 11 || \strlen($formatedValue) === 14) {
                    return $formatedValue;
                }
                throw new InvalidArgumentException;
            }
        );
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);

    }
}
