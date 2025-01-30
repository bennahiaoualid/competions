<?php

namespace App\Services\Competition;

use App\Interface\Competition\LevelRepositoryInterface;
use App\Models\Competition\Level;

class LevelService
{
    public function __construct(
        protected LevelRepositoryInterface $levelRepository
    ) {
    }

    public function create( array $data){
        return $this->levelRepository->create($data);
    }

    public function edit($id){
        return $this->levelRepository->edit($id);
    }

    public function update(Level $level, array $data){
        return $this->levelRepository->update($level, $data);
    }
    public function delete(Level $level){
        return $this->levelRepository->delete($level);
    }

    public function activateLevel($level_id){
        return $this->levelRepository->activateLevel($level_id);
    }

    public function finishLevel($level_id){
        return $this->levelRepository->finishLevel($level_id);
    }
}
