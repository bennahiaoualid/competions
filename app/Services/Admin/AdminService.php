<?php

namespace App\Services\Admin;

use App\Interface\Admin\AdminRepositoryInterface;
use App\Models\Admin\Admin;
use Exception;

class AdminService
{
    public function __construct(
        protected AdminRepositoryInterface $adminRepository
    ) {
    }

    public function index(){
        return $this->adminRepository->index();
    }


    public function all(){
        return $this->adminRepository->all();
    }

    public function create(array $data){
        return $this->adminRepository->create($data);
    }

    public function edit(Admin $admin){
        return $this->adminRepository->edit($admin);
    }

    public function update(Admin $admin, array $data){
        return $this->adminRepository->update($admin, $data);
    }

    public function delete(Admin $admin){
        return $this->adminRepository->delete($admin);
    }

}
