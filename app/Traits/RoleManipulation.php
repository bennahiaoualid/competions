<?php

namespace App\Traits;

use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;

trait RoleManipulation
{
    private ?Collection $cachedRoles = null;

    public function possibleRoles(): Collection
    {
        if ($this->cachedRoles !== null) {
            return $this->cachedRoles;
        }

        $role = Auth::user()->roles->pluck('name')->first();

        $this->cachedRoles = ($role === 'super_admin')
            ? Role::select('id', 'name')->whereNotIn('name', ['super_admin', 'owner'])->get()
            : Role::select('id', 'name')->get();

        return $this->cachedRoles;
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
