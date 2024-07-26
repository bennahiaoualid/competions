<?php

namespace App\Repository\Admin;

use App\Interface\Admin\AdminRepositoryInterface;
use App\Models\Admin\Admin;
use App\Models\User;
use App\Traits\RoleManipulation;
use Exception;
use App\Traits\RegisterLogs;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class AdminRepository implements AdminRepositoryInterface
{
    use RegisterLogs, RoleManipulation;
    function index(){
        $count = [
            "admin" => Admin::all()->count(),
            "user" => User::all()->count(),
        ];

        return view("pages.admin.dashboard",compact('count'));
    }

    function all(){
        $roles = $this->possibleRoles(Auth::user()->getRoleNames()->first());
        return view("pages.admin.admins.list", compact("roles"));
    }

    public function create(array $data){
        try {
            DB::beginTransaction();
            $admin =  Admin::create($data);
            $admin->roles()->sync([$data['role']]);
            DB::commit();
            return true;
        }
        catch (Exception $exception){
            DB::rollBack();
            $this->registerLogs('Admin creation error: ',$exception);
            return false;
        }

    }

    function edit(Admin $user){
        $roles = $this->possibleRoles(Auth::user()->getRoleNames()->first());
        return view("pages.admin.admins.edit-admin", compact("user", "roles"));
    }

    function update(Admin $admin, array $data){
        try {
            //dd($data);
            $admin->name = $data['name'];
            $admin->email = $data['email'];
            $admin->roles()->sync([$data['role']]);
            $admin->save();
            return true;
        }catch (Exception $exception){
            $this->registerLogs('Admin updating error: ',$exception);
            return false;
        }
    }

    function delete(Admin $admin){
        try {
            $admin->delete();
            return true;
        }catch (Exception $exception){
            $this->registerLogs('Admin deleting error: ',$exception);
            return false;
        }
    }
}
