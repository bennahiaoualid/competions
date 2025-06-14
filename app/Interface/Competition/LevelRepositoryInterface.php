<?php

namespace App\Interface\Competition;

use App\Models\Competition\Competition;
use App\Models\Competition\Level;
use Illuminate\Support\Collection;

interface LevelRepositoryInterface
{
    public function create(array $data): Level;
    public function update(Level $level, array $data): bool;
    public function delete(Level $level): bool;

    public function checkCompetitionMaxLevelNumbers(Competition $competition): bool;
    public function hasTimeConflict(int $competitionId, string $startDate, int $duration, ?int $excludeLevelId = null): bool;

    public function insertMissingResponsesForLevel(Level $level, int $batchSize = 500): void;
    public function assignAuditorsToUsersInPivot(Level $level, Collection $users, Collection $auditors): void;

}
