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
            ['name' => 'user.create'],
            ['name' => 'user.view'],
        ];
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                $permission,
                ['status' => 'active']
            );

        }
    }
}
