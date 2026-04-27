<?php

namespace App\Models;

use App\Exceptions\Auth\ForbiddenException;
use App\Helpers\DocumentHelper;
use App\Models\Scopes\OrganizationScope;
use App\Traits\HasSequencialNumber;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

#[ScopedBy([OrganizationScope::class])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory, HasSequencialNumber, HasUuids,SoftDeletes;

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

    protected static function booted()
    {

        static::updating(function (Client $client) {
            if ($client->isDirty('organization_id')) {
                throw new ForbiddenException('Organization of a client can´t be changed');
            }
        });
    }

    public function document(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                $formatedValue = DocumentHelper::formatCpfAndCnpj($value);

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

    public function address(): HasMany
    {
        return $this->hasMany(Address::class);
    }
}
