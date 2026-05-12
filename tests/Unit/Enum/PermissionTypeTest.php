<?php

use App\Enum\PermissionType;

describe('PermissionType', function () {

    it('should return all permissions for a group|model', function () {
        $permissions = PermissionType::byModel('user');
        expect($permissions)
            ->toHaveCount(4)
            ->each->toBeInstanceOf(PermissionType::class)
            ->and(array_column($permissions, 'value'))
            ->each->toStartWith('user.');
    });
});
