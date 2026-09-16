<?php

namespace App\Services;

use App\Actions\Validation\ValidatePasswordComplexity;
use App\Enum\PermissionType;
use App\Exceptions\CannotDeleteAdminException;
use App\Exceptions\InvalidFieldException;
use App\Models\Permission;
use App\Models\User;
use App\Services\TreatmentService\Strategies\TreatEmail;
use App\Services\TreatmentService\Strategies\TreatRegularString;
use App\Services\TreatmentService\TreatmentService;
use Illuminate\Database\Eloquent\Model;

class UserService extends BaseService
{
    public function __construct(
        private readonly TreatmentService $treatment,
        private readonly TreatRegularString $stringTreat,
        private readonly TreatEmail $emailTreat,
        private readonly ValidatePasswordComplexity $validatePasswordComplexity,
    ) {
        parent::__construct(new User);
    }

    public function create(array $data, User $user): User
    {
        $data['name'] = $this->treatment->for($this->stringTreat, $data['name'], 'name', $this->model)->mustBeNotNull()->handle();
        $data['email'] = $this->treatment->for($this->emailTreat, $data['email'], 'email', $this->model)->mustBeNotNull()->mustBeUnique()->handle();
        app(ValidatePasswordComplexity::class)->execute($data['password']);

        $newUser = new User([
            'name' => $data['name'],
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
            $user->name = $this->treatment->for($this->stringTreat, $data['name'], 'name', $this->model)->mustBeNotNull()->handle();
        }
        if (isset($data['email'])) {
            $user->email = $this->treatment->for($this->emailTreat, $data['email'], 'email', $this->model)
                ->mustBeNotNull()->mustBeUnique()->ignoredId($user->id)->handle();
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

        $data['email'] = $this->treatment->for($this->emailTreat, $data['email'], 'email', $this->model)
            ->mustBeNotNull()->mustBeUnique()->organizationId($data['organization_id'])->handle();

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
