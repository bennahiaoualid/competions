<?php

namespace App\Services\User;

use App\Models\User;
use App\Repository\User\UserRepository;

class UserService
{
    public function __construct(
        protected UserRepository $userRepository
    ) {
    }

    public function show(){
        return $this->userRepository->show();
    }

    public function edit($id){
        return $this->userRepository->edit($id);
    }

    public function create(array $data){
            return $this->userRepository->create($data);
    }

    public function update(User $user, array $data){
        return $this->userRepository->update($user, $data);
    }

    public function delete(User $user){
        return $this->userRepository->delete($user);
    }

}
