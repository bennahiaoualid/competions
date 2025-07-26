<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DeletionPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        Permission::create(['name' => 'hard delete user', 'guard_name' => 'admin']);
        Permission::create(['name' => 'hard delete admin', 'guard_name' => 'admin']);
        Permission::create(['name' => 'restore deleted entities', 'guard_name' => 'admin']);

        // Get roles
        $ownerRole = Role::where('name', 'owner')->where('guard_name', 'admin')->first();
        $superAdminRole = Role::where('name', 'super_admin')->where('guard_name', 'admin')->first();

        if ($ownerRole) {
            // Owner gets all permissions
            $ownerRole->givePermissionTo([
                'hard delete user',
                'hard delete admin',
                'restore deleted entities'
            ]);
        }

        if ($superAdminRole) {
            // Super admin gets user deletion and restore permissions (but not admin deletion)
            $superAdminRole->givePermissionTo([
                'hard delete user',
                'restore deleted entities'
            ]);
        }
    }
} 