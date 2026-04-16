<?php

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;

interface ResourceServiceInterface
{
    public function all(): Collection;

    public function findById(int $id): Model|JsonResource|null;

    public function create(array $data): Model|array;

    public function update(Model $model, array $data): Model;

    public function delete(Model $model): void;
}
