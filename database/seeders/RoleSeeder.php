<?php

namespace Database\Seeders;

use App\Models\Admin\Admin;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::create(['guard_name' => 'admin', 'name' => 'view admin']);
        Permission::create(['guard_name' => 'admin', 'name' => 'add admin']);
        Permission::create(['guard_name' => 'admin', 'name' => 'update admin']);
        Permission::create(['guard_name' => 'admin', 'name' => 'delete admin']);
        Permission::create(['guard_name' => 'admin', 'name' => 'add user']);
        Permission::create(['guard_name' => 'admin', 'name' => 'update user']);
        Permission::create(['guard_name' => 'admin', 'name' => 'delete user']);
        Permission::create(['guard_name' => 'admin', 'name' => 'view user']);

        $role_owner = Role::create(['guard_name' => 'admin', 'name' => 'owner']);

        $role_super = Role::create(['guard_name' => 'admin', 'name' => 'super_admin']);
        $role_super->givePermissionTo(
            "view admin",
            "add admin",
            "update admin",
            "delete admin",
            'add user',
            'update user',
            'delete user',
            'view user');
        $role_manager = Role::create(['guard_name' => 'admin', 'name' => 'manager']);
        $role_manager->givePermissionTo(
            "view admin",
            'add user',
            'update user',
            'delete user',
            'view user');

        $role_auditor = Role::create(['guard_name' => 'admin', 'name' => 'auditor']);
        (Admin::where('email', '=', 'oualidbennahia@gmail.com')->first())->assignRole($role_owner);
    }
}
