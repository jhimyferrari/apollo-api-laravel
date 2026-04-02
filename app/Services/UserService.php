<?php

namespace App\Services;

use App\Http\Resources\UserResource;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UserService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new User);
    }

    public function create(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);
        if (isset($data['permissions'])) {
            $permissions = Permission::whereIn('name', $data['permissions'])->get();
            $user->permissions()->sync($permissions);
        }

        return $user;
    }

    public function listAll(): ResourceCollection
    {
        $listOfUsers = UserResource::collection(User::with('permissions')->paginate(15));

        return $listOfUsers;
    }

    public function updatePermissions(User $user, array $data): void
    {
        $permissions = Permission::whereName($data['permissions'])->get();
        $user->permissions()->sync($permissions);

    }
}
