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
        $permissions = [
            // user
            ['name' => 'user.create'],
            ['name' => 'user.view'],
            ['name' => 'user.delete'],
            ['name' => 'user.update'],
            // client
            ['name' => 'client.create'],
            ['name' => 'client.view'],
            ['name' => 'client.delete'],
            ['name' => 'client.update'],
        ];
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                $permission,
                ['status' => 'active']
            );

        }
    }
}
