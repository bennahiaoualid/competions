<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::create(['guard_name' => 'admin', 'name' => 'add admin']);
        Permission::create(['guard_name' => 'admin', 'name' => 'view admin']);
        Permission::create(['guard_name' => 'admin', 'name' => 'update admin']);
        Permission::create(['guard_name' => 'admin', 'name' => 'delete admin']);

        Permission::create(['guard_name' => 'admin', 'name' => 'add user']);
        Permission::create(['guard_name' => 'admin', 'name' => 'update user']);
        Permission::create(['guard_name' => 'admin', 'name' => 'delete user']);
        Permission::create(['guard_name' => 'admin', 'name' => 'view user']);
    }
}
