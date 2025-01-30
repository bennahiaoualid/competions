<?php

namespace App\Services\User;

use App\Repository\User\UserCompetitionRepository;

class UserCompetitionService
{
    public function __construct(
        protected UserCompetitionRepository $userCompetitionRepository
    ) {
    }

    public function all(array $data, $user): \Illuminate\View\View
    {
        return $this->userCompetitionRepository->all($data ,$user);
    }

    public function competitionDetail($competition_id): \Illuminate\View\View
    {
        return $this->userCompetitionRepository->competitionDetail($competition_id);
    }

    public function levelDetail($level_id): \Illuminate\View\View
    {
        return $this->userCompetitionRepository->levelDetail($level_id);
    }
    public function levelStart($level_id): \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
    {
        return $this->userCompetitionRepository->levelStart($level_id);
    }
    public function storeResponse(array $data): \Illuminate\Http\RedirectResponse
    {
        return $this->userCompetitionRepository->storeResponse($data);
    }

    public function userResponses($level_id): \Illuminate\View\View
    {
        return $this->userCompetitionRepository->userResponses($level_id);
    }

    public function competitorsLevelOrder($level_id): \Illuminate\View\View
    {
        return $this->userCompetitionRepository->competitorsLevelOrder($level_id);
    }

    public function competitorsCompetitionOrder($copetitions_id): \Illuminate\View\View
    {
        return $this->userCompetitionRepository->competitorsCompetitionOrder($copetitions_id);
    }

}
