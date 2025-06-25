<?php

namespace App\Interface\Admin;

use App\Models\Admin\Admin;

interface AdminRepositoryInterface
{
    /**
     * Get the count of admins.
     */
    public function getAdminCount(): int;
    /**
     * Get the count of users.
     */
    public function getUserCount(): int;
    /**
     * Get all roles (no filtering logic here).
     */
    public function getAllRoles();
    /**
     * Create an admin.
     */
    public function create(array $data);
    /**
     * Update admin fields.
     */
    public function update(\App\Models\Admin\Admin $admin, array $data);
    /**
     * Delete an admin.
     */
    public function delete(\App\Models\Admin\Admin $admin);
}
