<?php

namespace App\Models;

use App\Helpers\DocumentHelper;
use App\Observers\OrganizationObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * @property string $id
 * @property string $name
 * @property string $document
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Brand> $brands
 * @property-read int|null $brands_count
 * @property-read Collection<int, Category> $categories
 * @property-read int|null $categories_count
 * @property-read Collection<int, Client> $clients
 * @property-read int|null $clients_count
 * @property-read Collection<int, Product> $products
 * @property-read int|null $products_count
 * @property-read Collection<int, SequencialNumber> $sequencialNumber
 * @property-read int|null $sequencial_number_count
 * @property-read Collection<int, Supplier> $suppliers
 * @property-read int|null $suppliers_count
 * @property-read Collection<int, User> $users
 * @property-read int|null $users_count
 *
 * @method static \Database\Factories\OrganizationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereDocument($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
#[ObservedBy(OrganizationObserver::class)]
class Organization extends Model
{
    use HasFactory,HasUuids;

    protected $table = 'organizations';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    protected $fillable = [
        'name',
        'document',
    ];

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

    /*
    * Relations of Model
    */
    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    public function sequencialNumber(): HasMany
    {
        return $this->hasMany(SequencialNumber::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
