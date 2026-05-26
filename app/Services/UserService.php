<?php

namespace App\Services;

use App\Actions\ValidateDuplicateField;
use App\Models\Permission;
use App\Models\User;

class UserService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new User);
    }

    public function create(array $data, User $user): User
    {

        app(ValidateDuplicateField::class)->execute(new User, 'email', $data['email'], $user->organization_id);
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
        $permissions = Permission::whereIn('name', $data['permissions'])->pluck('id');
        $user->permissions()->sync($permissions);

    }
}
