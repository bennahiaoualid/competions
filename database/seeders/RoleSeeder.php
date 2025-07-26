<?php

namespace Database\Seeders;

use App\Models\Admin\Admin;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $permissions = [
            'view admin',
            'add admin',
            'update admin',
            'delete admin',
            'hard_delete admin',
            'restore admin',
            'add user',
            'update user',
            'delete user',
            'hard_delete user',
            'restore user',
            'view user',
            'add competition'
        ];
        foreach($permissions as $permission){
            Permission::create(['guard_name' => 'admin', 'name' => $permission]);
        }

        $role_owner = Role::create(['guard_name' => 'admin', 'name' => 'owner']);
        $role_owner->givePermissionTo($permissions);

        $role_super = Role::create(['guard_name' => 'admin', 'name' => 'super_admin']);
        $role_super->givePermissionTo(
            'view admin',
            'add admin',
            'update admin',
            'delete admin',
            'view user',
            'add user',
            'update user',
            'delete user',
            'hard_delete user',
            'restore user',
            'add competition');
        $role_manager = Role::create(['guard_name' => 'admin', 'name' => 'manager']);
        $role_manager->givePermissionTo(
            'view admin',
            'add user',
            'update user',
            'delete user',
            'view user');
        
        $admin_owner = Admin::where('email', '=', 'oualidbennahia@gmail.com')->first();
        if ($admin_owner) {
            $admin_owner->assignRole($role_owner);
        }
    }
}
