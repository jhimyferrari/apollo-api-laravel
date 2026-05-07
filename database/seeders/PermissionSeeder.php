<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $resources = ['user', 'client', 'seller'];
        $actions = ['create', 'view', 'update', 'delete'];

        $permissions = [];
        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                $permissions[] = ['name' => "{$resource}.{$action}", 'status' => 'active'];
            }
        }

        Permission::upsert($permissions, ['name'], ['status']);

    }
}
