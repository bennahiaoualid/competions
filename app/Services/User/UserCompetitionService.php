<?php

namespace App\Services\User;

use App\Repository\User\UserCompetitionRepository;

class UserCompetitionService
{
    public function __construct(
        protected UserCompetitionRepository $userCompetitionRepository
    ) {
    }

    public function all(array $data): \Illuminate\View\View
    {
        return $this->userCompetitionRepository->all($data);
    }

}
