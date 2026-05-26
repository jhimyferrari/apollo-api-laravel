<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $organization_id
 * @property string $table
 * @property int $last_number
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Organization $organization
 * @method static \Database\Factories\SequencialNumberFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SequencialNumber newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SequencialNumber newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SequencialNumber query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SequencialNumber whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SequencialNumber whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SequencialNumber whereLastNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SequencialNumber whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SequencialNumber whereTable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SequencialNumber whereUpdatedAt($value)
 * @mixin \Eloquent
 */
#[Table('sequencial_numbers')]
class SequencialNumber extends Model
{
    /** @use HasFactory<SequencialNumberFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'table',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
