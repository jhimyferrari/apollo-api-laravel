<?php

namespace App\Models;

use App\Models\Scopes\OrganizationScope;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ScopedBy([OrganizationScope::class])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'clients';

    protected $primaryKey = 'id';

    public $timestamp = true;

    protected $fillable = [
        'document',
        'number',
        'legal_name',
        'trade_name',
        'state_registration',
        'phone',
        'email',
        'address_id',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Client $client) {
            if (auth()->check()) {
                $client->organization_id ??= auth()->user()->organization_id;
            }

        });
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);

    }

    public function address()
    {
        return $this->hasMany(Address::class);
    }
}
