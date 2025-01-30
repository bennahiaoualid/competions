<?php

namespace App\Interface\User;

use App\Models\User;

interface UserRepositoryInterface
{
    function index();
    function show();
    function create(array $data);
    function edit($id);
    function update(User $user , array $data);
    function delete(User $user);
}
