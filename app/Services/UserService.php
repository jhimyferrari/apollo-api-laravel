<?php

namespace App\Services;

use App\Actions\Treatment\TreatEmail;
use App\Actions\Treatment\TreatName;
use App\Actions\Validation\ValidatePasswordComplexity;
use App\Enum\PermissionType;
use App\Exceptions\CannotDeleteAdminException;
use App\Exceptions\InvalidFieldException;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserService extends BaseService
{
    public function __construct(
        private TreatName $treatName,
        private TreatEmail $treatEmail,
        private ValidatePasswordComplexity $validatePasswordComplexity
    ) {
        parent::__construct(new User);
    }

    public function create(array $data, User $user): User
    {
        $data['name'] = $this->treatName->execute(
            $this->model,
            'name',
            $data['name'],
            mustBeNotNull: true,
            mustBeUnique: false
        );
        $data['email'] = $this->treatEmail->execute(
            $this->model,
            'email',
            $data['email'],
            mustBeNotNull: true,
            mustBeUnique: true
        );
        app(ValidatePasswordComplexity::class)->execute($data['password']);

        $newUser = new User([
            'name' => trim($data['name']),
            'email' => $data['email'],
            'password' => $data['password'],
        ]);
        $newUser->organization_id = $user->organization_id;
        $newUser->save();

        if (isset($data['permissions'])) {
            $this->updatePermissions($newUser, $data['permissions']);
        }

        return $newUser;
    }

    /**
     * @param  User  $user
     */
    public function update(Model $user, array $data): User
    {
        if (isset($data['name'])) {
            $user->name = $this->treatName->execute(
                $this->model,
                'name',
                $data['name'],
                mustBeNotNull: true,
                mustBeUnique: false
            );
        }

        if (isset($data['email'])) {

            $user->email = $this->treatEmail->execute(
                $this->model,
                'email',
                $data['email'],
                mustBeNotNull: true,
                mustBeUnique: true
            );
        }

        $user->save();

        return $user;

    }

    /**
     * @param  User  $user;
     */
    public function delete(Model $user): void
    {
        if ($user->isAdministrator()) {
            throw new CannotDeleteAdminException;
        }

        $user->delete();
    }

    public function updatePermissions(User $user, array $permissions): void
    {
        $diff = array_diff($permissions, PermissionType::allValues());

        if (! empty($diff)) {

            throw new InvalidFieldException('The permissions ['.implode(',', $diff).'] doesn`t exist');
        }
        $permissions = Permission::whereIn('name', $permissions)->pluck('id');
        $user->permissions()->sync($permissions);

    }

    public function createAdmin(array $data): User
    {

        $data['email'] = $this->treatEmail->execute(
            $this->model,
            'email',
            $data['email'],
            mustBeNotNull: true,
            mustBeUnique: true,
            organizationId: $data['organization_id']
        );
        app(ValidatePasswordComplexity::class)->execute($data['password']);

        $adminUser = new User([
            'name' => 'Administrador',
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        $adminUser->organization_id = $data['organization_id'];
        $adminUser->is_administrator = true;
        $adminUser->save();
        $adminUser->permissions()->sync(Permission::all());

        return $adminUser;

    }
}
