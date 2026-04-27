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

    public function createWithOrganization(array $data, User $user): User
    {
        $newUser = new User([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);
        $newUser->organization_id = $user->organization_id;
        $newUser->save();

        if (isset($data['permissions'])) {
            $this->updatePermissions($newUser, $data);
        }

        return $newUser;
    }

    public function updatePermissions(User $user, array $data): void
    {
        $permissions = Permission::whereName($data['permissions'])->get();
        $user->permissions()->sync($permissions);

    }
}
