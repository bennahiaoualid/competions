<?php

namespace App\Interface\Admin;

use App\Models\Admin\Admin;

interface AdminRepositoryInterface
{
    function index();
    function all();
    function create(array $data);
    function edit(Admin $user);
    function update(Admin $admin , array $data);
    function delete(Admin $admin);
}
