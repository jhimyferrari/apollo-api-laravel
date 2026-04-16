<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\User;

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

    public function updatePermissions(User $user, array $data): void
    {
        $permissions = Permission::whereName($data['permissions'])->get();
        $user->permissions()->sync($permissions);

    }
}
