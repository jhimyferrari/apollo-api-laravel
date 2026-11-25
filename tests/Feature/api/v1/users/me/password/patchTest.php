<?php

declare(strict_types=1);

use App\Enum\PermissionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('PATCH /v1/users/me/password', function () {
    it('updates the authenticated user password and returns 204', function () {
        $oldPassword = $this->validPassword();
        $newPassword = $this->validPassword();
        $user = User::factory()->create([
            'password' => $oldPassword,
        ]);

        Sanctum::actingAs($user, [PermissionType::USER_UPDATE->value]);

        $this->patchJson(route('v1.users.me.password.update'), [
            'current_password' => $oldPassword,
            'password' => $newPassword,
        ])->assertNoContent();

        $user->refresh();
        expect(Hash::check($newPassword, $user->password))->toBeTrue()
            ->and(Hash::check($oldPassword, $user->password))->toBeFalse();
    });

    it('revokes other tokens of the user while keeping the current session token active', function () {
        $oldPassword = $this->validPassword();
        $newPassword = $this->validPassword();
        $user = User::factory()->create([
            'password' => $oldPassword,
        ]);
        $otherUser = User::factory()->create();

        $currentToken = $user->createToken('current-device', [PermissionType::USER_UPDATE->value, PermissionType::USER_READ->value]);
        $otherDeviceToken = $user->createToken('other-device', [PermissionType::USER_READ->value]);
        $otherUserToken = $otherUser->createToken('other-user-device');

        $this->withToken($currentToken->plainTextToken)
            ->patchJson(route('v1.users.me.password.update'), [
                'current_password' => $oldPassword,
                'password' => $newPassword,
            ])->assertNoContent();

        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $currentToken->accessToken->id,
        ]);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $otherDeviceToken->accessToken->id,
        ]);
        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $otherUserToken->accessToken->id,
        ]);

        expect(PersonalAccessToken::findToken($currentToken->plainTextToken))->not->toBeNull()
            ->and(PersonalAccessToken::findToken($otherDeviceToken->plainTextToken))->toBeNull();

        $this->app['auth']->forgetGuards();

        $this->withToken($otherDeviceToken->plainTextToken)
            ->postJson(route('v1.logout'))
            ->assertUnauthorized();
    });

    it('allows an administrator with user.update permission to change own password', function () {
        $oldPassword = $this->validPassword();
        $newPassword = $this->validPassword();
        $admin = User::factory()->admin()->create([
            'password' => $oldPassword,
        ]);

        Sanctum::actingAs($admin, [PermissionType::USER_UPDATE->value]);

        $this->patchJson(route('v1.users.me.password.update'), [
            'current_password' => $oldPassword,
            'password' => $newPassword,
        ])->assertNoContent();

        expect(Hash::check($newPassword, $admin->fresh()->password))->toBeTrue();
    });

    it('returns 401 when unauthenticated', function () {
        $this->patchJson(route('v1.users.me.password.update'), [
            'current_password' => $this->validPassword(),
            'password' => $this->validPassword(),
        ])
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHENTICATED')
            ->assertJsonPath('error.message', __('errors.unauthenticated'));
    });

    it('returns 401 when the token is invalid', function () {
        $this->withToken('1|invalid-token')
            ->patchJson(route('v1.users.me.password.update'), [
                'current_password' => $this->validPassword(),
                'password' => $this->validPassword(),
            ])
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    });

    it('allows an authenticated user without user.update permission to change own password', function () {
        $oldPassword = $this->validPassword();
        $newPassword = $this->validPassword();
        $user = User::factory()->create([
            'password' => $oldPassword,
            'is_administrator' => false,
        ]);
        $currentToken = $user->createToken('device-1', [PermissionType::PRODUCT_READ->value]);
        $otherDeviceToken = $user->createToken('device-2', [PermissionType::PRODUCT_READ->value]);

        $this->withToken($currentToken->plainTextToken)
            ->patchJson(route('v1.users.me.password.update'), [
                'current_password' => $oldPassword,
                'password' => $newPassword,
            ])->assertNoContent();

        $user->refresh();
        expect(Hash::check($newPassword, $user->password))->toBeTrue()
            ->and(PersonalAccessToken::findToken($currentToken->plainTextToken))->not->toBeNull()
            ->and(PersonalAccessToken::findToken($otherDeviceToken->plainTextToken))->toBeNull();
    });

    it('returns 422 when current_password is incorrect', function () {
        $oldPassword = $this->validPassword();
        $user = User::factory()->create([
            'password' => $oldPassword,
        ]);
        $user->createToken('device-1');

        Sanctum::actingAs($user, [PermissionType::USER_UPDATE->value]);

        $this->patchJson(route('v1.users.me.password.update'), [
            'current_password' => $this->validPassword(),
            'password' => $this->validPassword(),
        ])
            ->assertUnprocessable()
            ->assertJsonPath('error.fields.current_password.0', __('validation.current_password'));

        $user->refresh();
        expect(Hash::check($oldPassword, $user->password))->toBeTrue()
            ->and($user->tokens()->count())->toBe(1);
    });

    it('returns 422 when new password is the same as current_password', function () {
        $currentPassword = $this->validPassword();
        $user = User::factory()->create([
            'password' => $currentPassword,
        ]);
        $user->createToken('device-1');

        Sanctum::actingAs($user, [PermissionType::USER_UPDATE->value]);

        $this->patchJson(route('v1.users.me.password.update'), [
            'current_password' => $currentPassword,
            'password' => $currentPassword,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('error.fields.password.0', __('validation.different', [
                'attribute' => __('validation.attributes.password'),
                'other' => __('validation.attributes.current_password'),
            ]));

        $user->refresh();
        expect(Hash::check($currentPassword, $user->password))->toBeTrue()
            ->and($user->tokens()->count())->toBe(1);
    });

    it('returns 422 for invalid payload inputs', function (array $payload, string $errorField) {
        $currentPassword = 'OldStr0ng!Password';
        $user = User::factory()->create([
            'password' => $currentPassword,
        ]);

        Sanctum::actingAs($user, [PermissionType::USER_UPDATE->value]);

        $this->patchJson(route('v1.users.me.password.update'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($errorField, 'error.fields');

        expect(Hash::check($currentPassword, $user->fresh()->password))->toBeTrue();
    })->with([
        'empty payload' => [
            [],
            'current_password',
        ],
        'missing current_password' => [
            ['password' => 'NewStr0ng!Password'],
            'current_password',
        ],
        'missing password' => [
            ['current_password' => 'OldStr0ng!Password'],
            'password',
        ],
        'short password' => [
            ['current_password' => 'OldStr0ng!Password', 'password' => 'Ab1!cde'],
            'password',
        ],
        'weak password' => [
            ['current_password' => 'OldStr0ng!Password', 'password' => 'weakpassword'],
            'password',
        ],
        'array password' => [
            ['current_password' => 'OldStr0ng!Password', 'password' => array_fill(0, 8, 'a')],
            'password',
        ],
    ]);
});
