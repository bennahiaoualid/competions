<?php

namespace App\Repository\User;

use App\Interface\User\UserRepositoryInterface;
use App\Models\User;
use Exception;
use App\Traits\RegisterLogs;
use Illuminate\Support\Facades\Auth;



class UserRepository implements UserRepositoryInterface
{
    use RegisterLogs;
    function show(){
        return view("pages.admin.users.list");
    }
    function edit($id){
        $user = User::findorfail($id);
        return view("pages.admin.users.edit-user",compact("user"));
    }

    public function create(array $data){
        try {
            $data["admin_id"] = Auth::id();
            User::create($data);
            return true;
        }catch (Exception $exception){
            $this->registerLogs('User creation error: ',$exception);
            return false;
        }

    }

    function update(User $user, array $data){
        try {
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->save();
            return true;
        }catch (Exception $exception){
            $this->registerLogs('User updating error: ',$exception);
            return false;
        }
    }

    function delete(User $user){
        try {
            $user->delete();
            return true;
        }catch (Exception $exception){
            $this->registerLogs('User deleting error: ',$exception);
            return false;
        }
    }
}
