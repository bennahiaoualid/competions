<?php

namespace App\Repository\Admin;

use App\Interface\Admin\AdminRepositoryInterface;
use App\Models\Admin\Admin;
use App\Models\User;


class AdminRepository implements AdminRepositoryInterface
{
    /**
     * Get the count of admins and users.
     * Data access only. No business logic or view rendering.
     */
    public function getAdminCount(): int
    {
        return Admin::count();
    }

    public function getUserCount(): int
    {
        return User::count();
    }

    /**
     * Get all roles (data access only).
     * The business logic for filtering roles should be in the service.
     */
    public function getAllRoles()
    {
        // This should be called with any filtering already done in the service.
        return \Spatie\Permission\Models\Role::all();
    }

    /**
     * Create an admin (data access only).
     */
    public function create(array $data)
    {
        return Admin::create($data);
    }

    /**
     * Update admin fields (data access only).
     */
    public function update(Admin $admin, array $data)
    {
        $admin->fill($data);
        $admin->save();
        return $admin;
    }

    /**
     * Delete an admin (data access only).
     */
    public function delete(Admin $admin)
    {
        return $admin->delete();
    }
}
