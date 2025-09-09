<?php

namespace App\Interface\Competition;

use App\Models\Competition\Level;
use Illuminate\Support\Collection;

interface LevelRepositoryInterface
{
    public function create(array $data): Level;

    public function update(Level $level, array $data): bool;

    public function delete(Level $level): bool;

    public function hasTimeConflict(int $competitionId, string $startDate, int $duration, ?int $excludeLevelId = null): bool;

    public function insertMissingResponsesForLevel(Level $level, int $batchSize = 500): void;
    
    public function assignAuditorsToUsersInPivot(Level $level, Collection $users, Collection $auditors): void;

    public function isAdminAllowedToBeLevelManager($adminID) : bool;

    /**
     * Get counts of audited and not audited responses for a level (by level's questions)
     *
     * @param int $levelId
     * @return array{audited:int,not_audited:int,confirmed:int,not_confirmed:int}
     */
    public function getResponseAuditCounts(int $levelId, bool $ai_auditing): array;

    /**
     * re-assing users responses that not audited yet audting permmsion to the comptition creator
     *
     * @param int $levelId
     * @param int $creatorId
     */
    public function reAssignUsersResponsesAudtingPermission(int $levelId, int $creatorId);
}
