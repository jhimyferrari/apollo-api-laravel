<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Applies the organization scope to the query.
 *
 * **WARNING** The model must have an `organization_id` column.
 */
class OrganizationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! auth()->check()) {
            return;
        }

        $builder->where(
            $model->getTable().'.organization_id',
            auth()->user()->organization_id
        );
    }
}
