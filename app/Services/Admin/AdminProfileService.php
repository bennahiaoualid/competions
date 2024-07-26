<?php

namespace App\Services\Admin;

use App\Interface\Admin\AdminProfileRepositoryInterface;

class AdminProfileService
{
    public function __construct(
        protected AdminProfileRepositoryInterface $adminProfileRepository
    ) {
    }

}
