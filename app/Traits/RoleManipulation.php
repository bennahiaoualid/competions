<?php

namespace App\Traits;

use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;

trait RoleManipulation
{
    function possibleRoles(){
        $role = Auth::user()->getRoleNames()->first();
        if ($role == "super_admin"){
            return Role::whereNotIn('name', ['super_admin', 'owner'])->get();
        }
        else{
            return Role::all();
        }
    }
    function possibleRolesIds($role){
        if ($role == "super_admin"){
            return Role::whereNotIn('name', ['super_admin', 'owner'])->pluck('id');
        }
        else{
            return Role::pluck('id');
        }
    }
}
