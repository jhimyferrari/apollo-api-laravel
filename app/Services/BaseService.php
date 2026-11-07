<?php

namespace App\Services;

use App\Exceptions\ResourceHasDependenciesException;
use App\Interfaces\ResourceServiceInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Base service for the HTTP layer.
 *
 * Assumes an authenticated user is present — all methods
 * rely on active global scopes (e.g. OrganizationScope).
 */
abstract class BaseService implements ResourceServiceInterface
{
    public function __construct(protected Model $model) {}

    public function all(): Collection
    {
        return $this->model->all();
    }

    public function toResource(Model $model): ?JsonResource
    {
        return JsonResource::make($model);
    }

    public function findById(int $id): Model|JsonResource|null
    {
        return $this->model->find($id);
    }

    public function createWithoutOrganization(array $data): Model|array
    {
        return $this->model->create($data);
    }

    public function create(array $data, User $user): Model|array
    {
        $newModel = new $this->model($data);
        $newModel->organization_id = $user->organization_id;
        $newModel->save();

        return $newModel;
    }

    public function update(Model $model, array $data): Model
    {
        $model->fill($data);
        $model->save();

        return $model;
    }

    /**
     * Define relations that prevent deletion if records exist.
     *
     * @return array<string, string>
     */
    protected function dependentRelations(): array
    {
        return [];
    }

    /**
     * Ensure the model has no dependent relation records before deletion.
     *
     * @throws ResourceHasDependenciesException
     */
    protected function ensureHasNoDependencies(Model $model): void
    {
        $blocking = [];

        foreach ($this->dependentRelations() as $relation => $label) {
            if (method_exists($model, $relation) && $model->{$relation}()->exists()) {
                $blocking[] = $label;
            }
        }

        if (! empty($blocking)) {
            throw ResourceHasDependenciesException::withRelations($model, $blocking);
        }
    }

    public function delete(Model $model): void
    {
        $this->ensureHasNoDependencies($model);

        $model->delete();
    }
}
