<?php

use App\Enum\PermissionType;
use App\Exceptions\Auth\ForbiddenException;
use App\Exceptions\CannotDeleteAdminException;
use App\Models\Permission;
use App\Models\User;
use App\Services\UserService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
    $this->service = app(UserService::class);

});
describe('UserService', function () {
    describe('create', function () {
        it('should create an User successfully', function () {
            /** @var UserService $this->service */
            $user = $this->service->create([
                'name' => 'userName',
                'email' => 'user@email.com',
                'password' => $this->validPassword(),
            ], $this->user);

            expect($user)
                ->toBeInstanceOf(User::class)
                ->email->toBe('user@email.com');
        });

        it('should create an User with permissions successfully', function () {
            /** @var UserService $this->service */
            $this->seed(PermissionSeeder::class);
            $user = $this->service->create([
                'name' => 'userName',
                'email' => 'user@email.com',
                'password' => $this->validPassword(),
                'permissions' => PermissionType::byModel('client'),
            ], $this->user);

            expect($user)
                ->toBeInstanceOf(User::class)
                ->email->toBe('user@email.com');

            $permissionIds = Permission::whereIn('name', PermissionType::byModel('client'))->pluck('id');
            foreach ($permissionIds as $permissionId) {
                $this->assertDatabaseHas('user_permissions', [
                    'user_id' => $user->id,
                    'permission_id' => $permissionId,
                ]);
            }

        });
        it('should remove spaces from name', function () {
            /** @var UserService $this->service */
            $user = $this->service->create([
                'name' => ' nameWithSpaces',
                'email' => 'user@email.com',
                'password' => $this->validPassword(),
            ], $this->user);

            expect($user)
                ->toBeInstanceOf(User::class)
                ->name->toBe('nameWithSpaces');
        });
        it('throw an exception when using an used email', function () {
            /** @var UserService $this->service */
            $email = User::factory()->create(['organization_id' => $this->user->organization_id])->email;

            expect(fn () => $this->service->create([
                'name' => 'duplicatedEmail',
                'email' => $email,
                'password' => $this->validPassword(),
            ], $this->user))
                ->toThrow(ValidationException::class, __('validation.unique', ['attribute' => 'e-mail']));
        });
        it('throw an exception when using a weak password', function () {

            expect(fn () => $this->service->create([
                'name' => 'weakPassword',
                'email' => 'wake@email.com',
                'password' => 'weakPassword',
            ], $this->user))->toThrow(ValidationException::class);
        });

        it('throw an exception when not pass a name', function () {

            expect(fn () => $this->service->create([
                'email' => 'weak@email.com',
                'name' => '',
                'password' => $this->validPassword(),
            ], $this->user))->toThrow(ValidationException::class);
        });
        it('throw an exception when not pass an email', function () {

            expect(fn () => $this->service->create([
                'email' => '',
                'name' => 'weakPassword',
                'password' => $this->validPassword(),
            ], $this->user))->toThrow(ValidationException::class);
        });
        it('throw an exception when pass a permission that not exist', function () {

            expect(fn () => $this->service->create([
                'email' => 'permission@email.com',
                'name' => 'wrongPermission',
                'password' => $this->validPassword(),
                'permissions' => [...PermissionType::byModel('seller'), 'a', 'b', 'c'],
            ], $this->user))->toThrow(ValidationException::class, __('validation.permission.exist', ['values' => 'a,b,c']));
        });
        it('throw an exception when trying to create user with user control permissions', function () {

            expect(fn () => $this->service->create([
                'email' => 'user_perm@email.com',
                'name' => 'userPerm',
                'password' => $this->validPassword(),
                'permissions' => [PermissionType::USER_CREATE->value],
            ], $this->user))->toThrow(ValidationException::class, __('validation.permission.user_forbidden'));
        });
    });
    describe('update', function () {
        it('should update an user successfully', function () {
            $user = User::factory()->create(['organization_id' => $this->user->organization_id]);
            $user = $this->service->update(
                $user,
                [
                    'name' => 'newName',
                    'email' => 'newEmail@gmail.com',
                ]
            );
            expect($user)
                ->toBeInstanceOf(User::class)
                ->name->toBe('newName')

                ->email->toBe('newEmail@gmail.com');
        });

        it('should update just the name of an user', function () {
            $user = User::factory()->create(['organization_id' => $this->user->organization_id]);
            $email = $user->email;
            $user = $this->service->update(
                $user,
                [
                    'name' => 'newName',
                ]
            );
            expect($user)
                ->toBeInstanceOf(User::class)
                ->name->toBe('newName')
                ->email->toBe($email);
        });

        it('should update just the email of an user', function () {
            $user = User::factory()->create(['organization_id' => $this->user->organization_id]);
            $name = $user->name;
            $user = $this->service->update(
                $user,
                [
                    'email' => 'newEmail@gmail.com',
                ]
            );
            expect($user)
                ->toBeInstanceOf(User::class)
                ->name->toBe($name)
                ->email->toBe('newEmail@gmail.com');
        });

        it('throw an exception when pass a null name', function () {

            $user = User::factory()->create(['organization_id' => $this->user->organization_id]);
            expect(fn () => $this->service->update($user, [
                'name' => '',
            ]))->toThrow(ValidationException::class);
        });
        it('throw an exception when pass a null email', function () {

            $user = User::factory()->create(['organization_id' => $this->user->organization_id]);
            expect(fn () => $this->service->update($user, [
                'email' => '',
            ], $this->user))->toThrow(ValidationException::class);
        });

        it('should update name, email and permissions simultaneously', function () {
            $this->seed(PermissionSeeder::class);
            $user = User::factory()->create(['organization_id' => $this->user->organization_id]);
            $permissions = PermissionType::byModel('brand');

            $updatedUser = $this->service->update(
                $user,
                [
                    'name' => 'Updated Brand Manager',
                    'email' => 'brand_manager@test.com',
                    'permissions' => $permissions,
                ]
            );

            expect($updatedUser)
                ->toBeInstanceOf(User::class)
                ->name->toBe('Updated Brand Manager')
                ->email->toBe('brand_manager@test.com');

            $permissionIds = Permission::whereIn('name', $permissions)->pluck('id');
            foreach ($permissionIds as $permissionId) {
                $this->assertDatabaseHas('user_permissions', [
                    'user_id' => $user->id,
                    'permission_id' => $permissionId,
                ]);
            }
        });

        it('should update another user password and revoke all their tokens', function () {
            $oldPassword = $this->validPassword();
            $newPassword = $this->validPassword();
            $user = User::factory()->create([
                'organization_id' => $this->user->organization_id,
                'password' => $oldPassword,
            ]);
            $user->createToken('device-1');
            $this->user->createToken('actor-device');

            $updatedUser = $this->service->update($user, [
                'password' => $newPassword,
            ]);

            expect(Hash::check($newPassword, $updatedUser->password))->toBeTrue()
                ->and(Hash::check($oldPassword, $updatedUser->password))->toBeFalse()
                ->and($user->tokens()->count())->toBe(0)
                ->and($this->user->tokens()->count())->toBe(1);
        });

        it('should throw an exception when updating with weak or empty password', function (string $invalidPassword) {
            $user = User::factory()->create(['organization_id' => $this->user->organization_id]);

            expect(fn () => $this->service->update($user, [
                'password' => $invalidPassword,
            ]))->toThrow(ValidationException::class);
        })->with([
            'empty' => [''],
            'weak' => ['weakpassword'],
        ]);

        it('should throw ForbiddenException when updating the authenticated user themselves', function () {
            expect(fn () => $this->service->update($this->user, [
                'name' => 'Self Update',
            ]))->toThrow(ForbiddenException::class, __('errors.user_cannot_update_self'));
        });

        it('should throw ValidationException when non-admin tries to update an administrator password', function () {
            $admin = User::factory()->admin()->create(['organization_id' => $this->user->organization_id]);

            expect(fn () => $this->service->update($admin, [
                'password' => $this->validPassword(),
            ]))->toThrow(ValidationException::class, __('validation.admin_update'));
        });

        it('should selectively revoke tokens only when email, password, or permissions change', function () {
            $this->seed(PermissionSeeder::class);
            $user = User::factory()->create(['organization_id' => $this->user->organization_id]);
            $brandView = Permission::where('name', PermissionType::BRAND_READ->value)->firstOrFail();
            $user->permissions()->sync([$brandView->id]);
            $user->createToken('device-1');

            $this->service->update($user, ['name' => 'Only Name']);
            expect($user->tokens()->count())->toBe(1);

            $this->service->update($user, ['permissions' => [PermissionType::BRAND_READ->value]]);
            expect($user->tokens()->count())->toBe(1);

            $this->service->update($user, ['permissions' => [PermissionType::BRAND_CREATE->value]]);
            expect($user->tokens()->count())->toBe(0);

            $user->createToken('device-2');
            $this->service->update($user, ['email' => 'changed@test.com']);
            expect($user->tokens()->count())->toBe(0);
        });
    });

    describe('updateOwnPassword', function () {
        it('should update own password, keep current PersonalAccessToken, and revoke other tokens', function () {
            $oldPassword = $this->validPassword();
            $newPassword = $this->validPassword();
            $user = User::factory()->create([
                'password' => $oldPassword,
            ]);
            $otherUser = User::factory()->create();

            $currentToken = $user->createToken('current-device');
            $otherToken = $user->createToken('other-device');
            $otherUserToken = $otherUser->createToken('other-user-device');

            $user->withAccessToken($currentToken->accessToken);

            $updatedUser = $this->service->updateOwnPassword($user, $oldPassword, $newPassword);

            expect(Hash::check($newPassword, $updatedUser->password))->toBeTrue();
            $this->assertDatabaseHas('personal_access_tokens', ['id' => $currentToken->accessToken->id]);
            $this->assertDatabaseMissing('personal_access_tokens', ['id' => $otherToken->accessToken->id]);
            $this->assertDatabaseHas('personal_access_tokens', ['id' => $otherUserToken->accessToken->id]);
        });

        it('should revoke all tokens when currentAccessToken is not a PersonalAccessToken', function () {
            $oldPassword = $this->validPassword();
            $newPassword = $this->validPassword();
            $user = User::factory()->create([
                'password' => $oldPassword,
            ]);
            $user->createToken('device-1');
            Sanctum::actingAs($user);

            $this->service->updateOwnPassword($user, $oldPassword, $newPassword);

            expect(Hash::check($newPassword, $user->fresh()->password))->toBeTrue()
                ->and($user->tokens()->count())->toBe(0);
        });

        it('should throw ValidationException when current_password is incorrect', function () {
            $user = User::factory()->create([
                'password' => $this->validPassword(),
            ]);

            expect(fn () => $this->service->updateOwnPassword($user, $this->validPassword(), $this->validPassword()))
                ->toThrow(ValidationException::class, __('validation.current_password'));
        });

        it('should throw ValidationException when new password matches current password', function () {
            $currentPassword = $this->validPassword();
            $user = User::factory()->create([
                'password' => $currentPassword,
            ]);

            expect(fn () => $this->service->updateOwnPassword($user, $currentPassword, $currentPassword))
                ->toThrow(ValidationException::class);
        });

        it('should throw ValidationException when new password fails complexity', function () {
            $currentPassword = $this->validPassword();
            $user = User::factory()->create([
                'password' => $currentPassword,
            ]);

            expect(fn () => $this->service->updateOwnPassword($user, $currentPassword, 'weak'))
                ->toThrow(ValidationException::class);
        });
    });
    describe('delete', function () {
        it('should delete a regular user', function () {
            $user = User::factory()->create();

            $this->service->delete($user);

            $this->assertSoftDeleted($user);
        });

        it('throw an error when try to delete an admin user', function () {
            $user = User::factory()->admin()->create();

            expect(fn () => $this->service->delete($user))->toThrow(CannotDeleteAdminException::class);

        });
    });
    describe('updatePermissions', function () {
        it('should update permission of an user', function () {
            $this->seed(PermissionSeeder::class);
            $user = User::factory()->create();

            $this->service->updatePermissions($user, PermissionType::byModel('seller'));

            $permissionIds = Permission::whereIn('name', PermissionType::byModel('seller'))->pluck('id');
            foreach ($permissionIds as $permissionId) {
                $this->assertDatabaseHas('user_permissions', [
                    'user_id' => $user->id,
                    'permission_id' => $permissionId,
                ]);
            }
        });

        it('throw an exception when trying to assign user control permissions', function () {
            $this->seed(PermissionSeeder::class);
            $user = User::factory()->create();

            expect(fn () => $this->service->updatePermissions($user, [PermissionType::USER_UPDATE->value]))
                ->toThrow(ValidationException::class, __('validation.permission.user_forbidden'));
        });
    });
    describe('createAdmin', function () {
        it('should create an administrator user successfully', function () {
            $user = $this->service->createAdmin([
                'email' => fake()->email,
                'password' => $this->validPassword(),
                'organization_id' => $this->user->organization_id,
            ]);
            expect($user)
                ->toBeInstanceOf(User::class)
                ->name->toBe('Administrador')
                ->is_administrator->toBeTrue();

        });
    });
});
