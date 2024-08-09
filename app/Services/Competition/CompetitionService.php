<?php

namespace App\Services\Competition;

use App\Interface\Competition\CompetitionRepositoryInterface;
use App\Models\Competition\Competition;

class CompetitionService
{
    public function __construct(
        protected CompetitionRepositoryInterface $competitionRepository
    ) {
    }

    public function all(){
        return $this->competitionRepository->all();
    }

    public function create(array $data){
        return $this->competitionRepository->create($data);
    }

    public function edit($id){
        return $this->competitionRepository->edit($id);
    }

    public function update(Competition $competition, array $data){
        return $this->competitionRepository->update($competition, $data);
    }

    function getCompetitionUsers($competition_id){
        return $this->competitionRepository->getCompetitionUsers($competition_id);
    }

    function removeCompetitionUser($competition_id, $user_id){
        return $this->competitionRepository->removeCompetitionUser($competition_id, $user_id);
    }

    function addCompetitionUsers($competition_id, $user_ids){
        return $this->competitionRepository->addCompetitionUsers($competition_id, $user_ids);
    }

}
