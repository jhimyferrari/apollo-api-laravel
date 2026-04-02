<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Permission;
use App\Models\User;

class OrganizationService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new Organization);
    }

    public function create(array $data): array
    {
        $organization = Organization::create([
            'name' => $data['name'],
            'document' => $data['document'],
        ]);

        $adminUser = new User([
            'name' => 'Administrador',
            'email' => $data['email'],
            'password' => $data['password'],
        ]);
        $adminUser->organization_id = $organization->id;
        $adminUser->is_administrator = true;
        $adminUser->save();

        $adminUser->permissions()->sync(Permission::all());

        return [$organization, $adminUser];
    }
}
