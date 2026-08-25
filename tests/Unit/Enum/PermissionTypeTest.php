<?php

use App\Enum\PermissionType;

describe('PermissionType', function () {

    it('should return all permissions values ', function () {
        $permissions = PermissionType::allValues();
        expect($permissions)
            ->toHaveCount(count(PermissionType::cases()))
            ->not->toHavekey('name', 'value');
    });
    it('should return all permissions for a group|model', function () {
        $permissions = PermissionType::byModel('user');
        expect($permissions)
            ->toHaveCount(4)
            ->each->toStartWith('user.');
    });
});
