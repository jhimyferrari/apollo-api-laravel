<?php

namespace App\Services;

use App\Interfaces\ResourceServiceInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;

class BaseService implements ResourceServiceInterface
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

    public function createWithOrganization(array $data, User $user): Model
    {
        $newModel = new $this->model($data);
        $newModel->organization_id = $user->organization_id;

        $newModel->save();

        return $newModel;
    }

    public function create(array $data): Model|array
    {
        return $this->model->create($data);
    }

    public function update(Model $model, array $data): Model
    {
        $model->fill($data);
        $model->save();

        return $model;
    }

    public function delete(Model $model): void
    {
        $model->delete();
    }
}
