<?php

namespace App\Models;

use App\Exceptions\Auth\ForbiddenException;
use App\Models\Scopes\OrganizationScope;
use App\Policies\UserPolicy;
use Database\Factories\UserFactory;
// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[ScopedBy([OrganizationScope::class])]
#[UsePolicy(UserPolicy::class)]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUuids, Notifiable,SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function booted()
    {
        static::creating(function (User $user) {
            if (auth()->check()) {
                $user->organization_id ??= auth()->user()->organization_id;
            }
        });
        static::updating(function (User $user) {
            if ($user->isDirty('organization_id')) {
                throw new ForbiddenException('Organization of an user can´t be changed');
            }
            if ($user->isDirty('is_administrator')) {
                throw new ForbiddenException('Administrator atribute can´t be changed');
            }
        });
        static::deleting(function (User $user) {
            if ($user->isAdministrator()) {
                throw new ForbiddenException('The administrator can´t be removed');
            }
        });
    }

    public function isAdministrator(): bool
    {
        return $this->is_administrator;
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permissions');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
