<?php

namespace App\Services;

use App\Actions\Validation\ValidatePasswordComplexity;
use App\Enum\PermissionType;
use App\Exceptions\Auth\ForbiddenException;
use App\Exceptions\CannotDeleteAdminException;
use App\Models\Permission;
use App\Models\User;
use App\Services\TreatmentService\Strategies\TreatEmail;
use App\Services\TreatmentService\Strategies\TreatRegularString;
use App\Services\TreatmentService\TreatmentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

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
        if (auth()->check() && auth()->id() === $user->id) {
            throw new ForbiddenException(__('errors.user_cannot_update_self'));
        }

        if ($user->isAdministrator() && auth()->check() && auth()->id() !== $user->id) {
            if (
                \array_key_exists('name', $data)
                || \array_key_exists('email', $data)
                || \array_key_exists('password', $data)
            ) {
                throw ValidationException::withMessages(['user' => __('validation.admin_update')]);
            }
        }

        DB::transaction(function () use ($user, $data): void {
            if (isset($data['name'])) {
                $user->name = $this->treatment->for($this->stringTreat, $data['name'], 'name', $this->model)->mustBeNotNull()->handle();
            }

            if (isset($data['email'])) {
                $user->email = $this->treatment->for($this->emailTreat, $data['email'], 'email', $this->model)
                    ->mustBeNotNull()->mustBeUnique()->ignoredId($user->id)->handle();
            }

            if (\array_key_exists('password', $data)) {
                if (! \is_string($data['password'])) {
                    throw ValidationException::withMessages([
                        'password' => __('validation.string', ['attribute' => __('validation.attributes.password')]),
                    ]);
                }

                $this->validatePasswordComplexity->execute($data['password']);
                $user->password = $data['password'];
            }

            $permissionsChanged = false;

            if (\array_key_exists('permissions', $data)) {
                if ($user->isAdministrator()) {
                    throw ValidationException::withMessages(['permission' => __('validation.permission.admin_forbidden')]);
                }

                $changes = $this->updatePermissions($user, $data['permissions']);
                $permissionsChanged = ! empty($changes['attached']) || ! empty($changes['detached']) || ! empty($changes['updated']);
            }

            $shouldRevokeTokens = $user->isDirty(['email', 'password']) || $permissionsChanged;

            $user->save();

            if ($shouldRevokeTokens) {
                $user->tokens()->delete();
            }
        });

        return $user;
    }

    public function updateOwnPassword(User $user, string $currentPassword, string $newPassword): User
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => __('validation.current_password'),
            ]);
        }

        if (Hash::check($newPassword, $user->password)) {
            throw ValidationException::withMessages([
                'password' => __('validation.different', [
                    'attribute' => __('validation.attributes.password'),
                    'other' => __('validation.attributes.current_password'),
                ]),
            ]);
        }

        $this->validatePasswordComplexity->execute($newPassword);

        DB::transaction(function () use ($user, $newPassword): void {
            $user->password = $newPassword;
            $user->save();

            $currentToken = $user->currentAccessToken();

            if ($currentToken instanceof PersonalAccessToken) {
                $user->tokens()->whereKeyNot($currentToken->getKey())->delete();
            } else {
                $user->tokens()->delete();
            }
        });

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
        DB::transaction(function () use ($user) {
            $user->tokens()->delete();
            $user->delete();
        });
    }

    public function updatePermissions(User $user, array $permissions): array
    {
        $diff = array_diff($permissions, PermissionType::allValues());

        if (! empty($diff)) {
            throw ValidationException::withMessages(['permission' => __('validation.permission.exist', ['values' => implode(',', $diff)])]);
        }

        $userControlPermissions = array_intersect($permissions, PermissionType::byModel('user'));

        if (! empty($userControlPermissions)) {
            throw ValidationException::withMessages(['permission' => __('validation.permission.user_forbidden')]);
        }

        $permissions = Permission::whereIn('name', $permissions)->pluck('id');

        return $user->permissions()->sync($permissions);
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
