<?php

use App\Enum\PermissionType;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
describe('PATCH api/users/{user}', function () {

    test('Logged user with valid data', function () {
        $this->seed(PermissionSeeder::class);

        $organization = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $organization->id,
        ]);
        $secondUser = User::factory()->create([
            'organization_id' => $organization->id,
        ]);

        Sanctum::actingAs($user, [PermissionType::USER_UPDATE->value]);
        $permissions = Permission::where('name', 'like', 'client.%')->pluck('name');
        $response = $this->patchJson(route('v1.users.update', $secondUser), [
            'name' => 'Updated Name',
            'email' => 'updated_email@test.com',
            'permissions' => $permissions,
        ]);

        $response->assertNoContent();

        $secondUser->refresh();
        expect($secondUser->name)->toBe('Updated Name')
            ->and($secondUser->email)->toBe('updated_email@test.com');

        $permissionIds = Permission::whereIn('name', $permissions)->pluck('id');
        foreach ($permissionIds as $permissionId) {
            $this->assertDatabaseHas('user_permissions', [
                'user_id' => $secondUser->id,
                'permission_id' => $permissionId,
            ]);
        }

        // User for other organization
        $otherUser = User::factory()->create();

        $response = $this->patchJson(route('v1.users.update', $otherUser), [
            'permissions' => $permissions,
        ]);
        $response->assertNotFound();

    });

    test('Logged user cannot assign user control permissions', function () {
        $this->seed(PermissionSeeder::class);

        $organization = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $organization->id,
        ]);
        $secondUser = User::factory()->create([
            'organization_id' => $organization->id,
        ]);

        Sanctum::actingAs($user, [PermissionType::USER_UPDATE->value]);
        $userPermissions = Permission::where('name', 'like', 'user.%')->pluck('name');
        $response = $this->patchJson(route('v1.users.update', $secondUser), [
            'permissions' => $userPermissions,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonPath('error.fields.permission.0', __('validation.permission.user_forbidden'));
    });

    test('Logged user with non valid data', function () {
        $this->seed(PermissionSeeder::class);
        $user = User::factory()->create();
        $secondUser = User::factory()->for($user->organization)->create();

        Sanctum::actingAs($user, [PermissionType::USER_UPDATE->value]);
        $response = $this->patchJson(route('v1.users.update', $secondUser), []);
        $response->assertUnprocessable();

        $response = $this->patchJson(route('v1.users.update', $secondUser), [
            'permissions' => ['non.existed.permission'],
        ]);

        $response->assertUnprocessable();
    });

    test('Non logged user', function () {
        $user = User::factory()->create(
        );
        $response = $this->patchJson(route('v1.users.update', $user), []);
        $response->assertUnauthorized();
    });

    test('Logged user without permission', function () {
        $user = User::factory()->create(
        );
        Sanctum::actingAs(User::factory()->create());
        $response = $this->patchJson(route('v1.users.update', $user), [
        ]);
        $response->assertNotFound();
    });

    test('Logged user can remove all permissions by sending empty array', function () {
        $this->seed(PermissionSeeder::class);
        $user = User::factory()->create();
        $targetUser = User::factory()->for($user->organization)->create();
        $permission = Permission::where('name', PermissionType::CLIENT_READ->value)->firstOrFail();
        $targetUser->permissions()->sync([$permission->id]);

        Sanctum::actingAs($user, [PermissionType::USER_UPDATE->value]);

        $response = $this->patchJson(route('v1.users.update', $targetUser), [
            'permissions' => [],
        ]);

        $response->assertNoContent();
        expect($targetUser->fresh()->permissions)->toHaveCount(0);
    });

    test('Logged user cannot update user with duplicate permissions', function () {
        $this->seed(PermissionSeeder::class);
        $user = User::factory()->create();
        $targetUser = User::factory()->for($user->organization)->create();

        Sanctum::actingAs($user, [PermissionType::USER_UPDATE->value]);

        $response = $this->patchJson(route('v1.users.update', $targetUser), [
            'permissions' => [PermissionType::CLIENT_READ->value, PermissionType::CLIENT_READ->value],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('permissions.0', 'error.fields');
    });

    test('Non-admin user cannot modify administrator name or email', function () {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->for($user->organization)->create([
            'name' => 'Administrador',
            'email' => 'admin@org.test',
        ]);

        Sanctum::actingAs($user, [PermissionType::USER_UPDATE->value]);

        $response = $this->patchJson(route('v1.users.update', $admin), [
            'name' => 'Hacked Name',
            'email' => 'hacked@org.test',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('user', 'error.fields');
        expect($admin->fresh()->email)->toBe('admin@org.test');
    });

    test('Logged user with permission can update another user password and revokes their tokens', function () {
        $organization = Organization::factory()->create();
        $oldPassword = $this->validPassword();
        $newPassword = $this->validPassword();

        $actor = User::factory()->for($organization)->create();
        $actorToken = $actor->createToken('actor-token', [PermissionType::USER_UPDATE->value]);

        $targetUser = User::factory()->for($organization)->create([
            'password' => $oldPassword,
        ]);
        $targetToken1 = $targetUser->createToken('device-1', [PermissionType::USER_READ->value])->plainTextToken;
        $targetUser->createToken('device-2');

        $response = $this->withToken($actorToken->plainTextToken)
            ->patchJson(route('v1.users.update', $targetUser), [
                'password' => $newPassword,
            ]);

        $response->assertNoContent();

        $targetUser->refresh();
        expect(Hash::check($newPassword, $targetUser->password))->toBeTrue()
            ->and(Hash::check($oldPassword, $targetUser->password))->toBeFalse()
            ->and($targetUser->tokens()->count())->toBe(0)
            ->and($actor->tokens()->count())->toBe(1);

        $this->app['auth']->forgetGuards();

        $this->withToken($targetToken1)
            ->getJson(route('v1.users.index'))
            ->assertUnauthorized();
    });

    test('Logged user in same organization without user.update permission receives 403 when updating password', function () {
        $organization = Organization::factory()->create();
        $oldPassword = $this->validPassword();
        $actor = User::factory()->for($organization)->create();
        $targetUser = User::factory()->for($organization)->create([
            'password' => $oldPassword,
        ]);
        $targetUser->createToken('device-1');

        Sanctum::actingAs($actor, [PermissionType::USER_READ->value]);

        $response = $this->patchJson(route('v1.users.update', $targetUser), [
            'password' => $this->validPassword(),
        ]);

        $response->assertForbidden();

        $targetUser->refresh();
        expect(Hash::check($oldPassword, $targetUser->password))->toBeTrue()
            ->and($targetUser->tokens()->count())->toBe(1);
    });

    test('Logged user cannot update password of user from another organization', function () {
        $oldPassword = $this->validPassword();
        $actor = User::factory()->create();
        $otherOrgUser = User::factory()->create([
            'password' => $oldPassword,
        ]);
        $otherOrgUser->createToken('device-1');

        Sanctum::actingAs($actor, [PermissionType::USER_UPDATE->value]);

        $response = $this->patchJson(route('v1.users.update', $otherOrgUser), [
            'password' => $this->validPassword(),
        ]);

        $response->assertNotFound();

        $otherOrgUser->refresh();
        expect(Hash::check($oldPassword, $otherOrgUser->password))->toBeTrue()
            ->and($otherOrgUser->tokens()->count())->toBe(1);
    });

    test('Logged user receives 422 when updating another user with invalid password', function (mixed $invalidPassword) {
        $oldPassword = $this->validPassword();
        $actor = User::factory()->create();
        $targetUser = User::factory()->for($actor->organization)->create([
            'password' => $oldPassword,
        ]);
        $targetUser->createToken('device-1');

        Sanctum::actingAs($actor, [PermissionType::USER_UPDATE->value]);

        $response = $this->patchJson(route('v1.users.update', $targetUser), [
            'password' => $invalidPassword,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('password', 'error.fields');

        $targetUser->refresh();
        expect(Hash::check($oldPassword, $targetUser->password))->toBeTrue()
            ->and($targetUser->tokens()->count())->toBe(1);
    })->with([
        'too short' => ['Ab1!cde'],
        'missing complexity' => ['weakpassword'],
        'array value' => [array_fill(0, 8, 'a')],
    ]);

    test('Non-admin user cannot modify administrator password', function () {
        $oldPassword = $this->validPassword();
        $actor = User::factory()->create();
        $admin = User::factory()->admin()->for($actor->organization)->create([
            'password' => $oldPassword,
        ]);
        $admin->createToken('admin-device');

        Sanctum::actingAs($actor, [PermissionType::USER_UPDATE->value]);

        $response = $this->patchJson(route('v1.users.update', $admin), [
            'password' => $this->validPassword(),
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('error.fields.user.0', __('validation.admin_update'));

        $admin->refresh();
        expect(Hash::check($oldPassword, $admin->password))->toBeTrue()
            ->and($admin->tokens()->count())->toBe(1);
    });

    test('Logged user cannot update themselves via PATCH /v1/users/{user}', function (bool $isAdmin, array $payload) {
        $oldPassword = $this->validPassword();
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@org.test',
            'password' => $oldPassword,
            'is_administrator' => $isAdmin,
        ]);
        $user->createToken('device-1');

        Sanctum::actingAs($user, [PermissionType::USER_UPDATE->value]);

        $response = $this->patchJson(route('v1.users.update', $user), $payload);

        $response->assertForbidden()
            ->assertJsonPath('error.message', __('errors.forbidden'));

        $user->refresh();
        expect($user->name)->toBe('Original Name')
            ->and($user->email)->toBe('original@org.test')
            ->and(Hash::check($oldPassword, $user->password))->toBeTrue()
            ->and($user->tokens()->count())->toBe(1);
    })->with([
        'regular user updating name' => [false, ['name' => 'Changed Name']],
        'regular user updating email' => [false, ['email' => 'changed@org.test']],
        'regular user updating password' => [false, ['password' => 'Str0ng!Pass123']],
        'admin updating password' => [true, ['password' => 'Str0ng!Pass123']],
    ]);

    test('Token revocation is selective on user update', function () {
        $this->seed(PermissionSeeder::class);
        $actor = User::factory()->create();
        $targetUser = User::factory()->for($actor->organization)->create();
        $clientRead = Permission::where('name', PermissionType::CLIENT_READ->value)->firstOrFail();
        $clientCreate = Permission::where('name', PermissionType::CLIENT_CREATE->value)->firstOrFail();
        $targetUser->permissions()->sync([$clientRead->id]);
        $targetUser->createToken('device-1');

        Sanctum::actingAs($actor, [PermissionType::USER_UPDATE->value]);

        // Updating only name does not revoke tokens
        $this->patchJson(route('v1.users.update', $targetUser), [
            'name' => 'Renamed User',
        ])->assertNoContent();
        expect($targetUser->tokens()->count())->toBe(1);

        // Sending identical permissions does not revoke tokens
        $this->patchJson(route('v1.users.update', $targetUser), [
            'permissions' => [PermissionType::CLIENT_READ->value],
        ])->assertNoContent();
        expect($targetUser->tokens()->count())->toBe(1);

        // Changing permissions revokes tokens
        $this->patchJson(route('v1.users.update', $targetUser), [
            'permissions' => [PermissionType::CLIENT_CREATE->value],
        ])->assertNoContent();
        expect($targetUser->tokens()->count())->toBe(0);

        // Changing email revokes tokens
        $targetUser->createToken('device-2');
        $this->patchJson(route('v1.users.update', $targetUser), [
            'email' => 'new_email@org.test',
        ])->assertNoContent();
        expect($targetUser->tokens()->count())->toBe(0);
    });

});
