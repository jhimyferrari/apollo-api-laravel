<?php

declare(strict_types=1);

use App\Enum\PermissionType;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('GET /v1/me', function () {
    it('returns the authenticated administrator profile and permissions', function () {
        $this->seed(PermissionSeeder::class);
        $organization = Organization::factory()->create();
        $admin = User::factory()->admin()->create([
            'organization_id' => $organization->id,
        ]);
        $admin->permissions()->attach(Permission::all()->pluck('id'));

        $token = $admin->createToken('web-bff', Permission::all()->pluck('name')->toArray())->plainTextToken;

        $this->withToken($token)
            ->getJson(route('v1.me'))
            ->assertOk()
            ->assertJsonPath('data.id', $admin->id)
            ->assertJsonPath('data.name', $admin->name)
            ->assertJsonPath('data.email', $admin->email)
            ->assertJsonPath('data.is_administrator', true)
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.organization_id')
            ->assertJsonMissingPath('data.deleted_at');
    });

    it('returns the authenticated operator profile with partial permissions', function () {
        $this->seed(PermissionSeeder::class);
        $organization = Organization::factory()->create();
        $operator = User::factory()->create([
            'organization_id' => $organization->id,
            'is_administrator' => false,
        ]);
        $perm = Permission::where('name', PermissionType::PRODUCT_READ->value)->firstOrFail();
        $operator->permissions()->attach($perm->id);

        $token = $operator->createToken('web-bff', [$perm->name])->plainTextToken;

        $this->withToken($token)
            ->getJson(route('v1.me'))
            ->assertOk()
            ->assertJsonPath('data.id', $operator->id)
            ->assertJsonPath('data.is_administrator', false)
            ->assertJsonCount(1, 'data.permissions')
            ->assertJsonPath('data.permissions.0.name', PermissionType::PRODUCT_READ->value);
    });

    it('returns 401 when unauthenticated', function () {
        $this->getJson(route('v1.me'))
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    });

    it('returns 401 when the token is invalid or expired', function () {
        $user = User::factory()->create();
        $expired = $user->createToken('expired-token', [], now()->subMinute());

        $this->withToken($expired->plainTextToken)
            ->getJson(route('v1.me'))
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');

        $this->withToken('1|non-existent-token')
            ->getJson(route('v1.me'))
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    });
});
