<?php

use App\Enum\PermissionType;
use App\Exceptions\CannotDeleteAdminException;
use App\Exceptions\DuplicateFieldException;
use App\Exceptions\InvalidFieldException;
use App\Exceptions\PasswordValidationException;
use App\Models\Permission;
use App\Models\User;
use App\Services\UserService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ], $this->user))->toThrow(DuplicateFieldException::class, "User email `$email` already exist");
        });
        it('throw an exception when using a weak password', function () {

            expect(fn () => $this->service->create([
                'name' => 'weakPassword',
                'email' => 'wake@email.com',
                'password' => 'weakPassword',
            ], $this->user))->toThrow(PasswordValidationException::class);
        });

        it('throw an exception when not pass a name', function () {

            expect(fn () => $this->service->create([
                'email' => 'weak@email.com',
                'name' => '',
                'password' => $this->validPassword(),
            ], $this->user))->toThrow(InvalidFieldException::class);
        });
        it('throw an exception when not pass an email', function () {

            expect(fn () => $this->service->create([
                'email' => '',
                'name' => 'weakPassword',
                'password' => $this->validPassword(),
            ], $this->user))->toThrow(InvalidFieldException::class);
        });
        it('throw an exception when pass a permission that not exist', function () {

            expect(fn () => $this->service->create([
                'email' => 'permission@email.com',
                'name' => 'wrongPermission',
                'password' => $this->validPassword(),
                'permissions' => [...PermissionType::byModel('seller'), 'a', 'b', 'c'],
            ], $this->user))->toThrow(InvalidFieldException::class, 'The permissions [a,b,c] doesn`t exist');
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
            ]))->toThrow(InvalidFieldException::class);
        });
        it('throw an exception when pass a null email', function () {

            $user = User::factory()->create(['organization_id' => $this->user->organization_id]);
            expect(fn () => $this->service->update($user, [
                'email' => '',
            ], $this->user))->toThrow(InvalidFieldException::class);
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
