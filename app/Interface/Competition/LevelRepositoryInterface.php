<?php

namespace App\Interface\Competition;

use App\Models\Admin\Admin;
use App\Models\Competition\Competition;
use App\Models\Competition\Level;
use App\Models\User;
use Illuminate\Support\Collection;

interface LevelRepositoryInterface
{
    public function create(array $data): Level;
    public function findById(int $id): ?Level;
    public function findOrFail(int $id): Level;
    public function findDecodedOrFail(string $encodedId): Level;
    public function getAllAdmins(): Collection;
    public function update(Level $level, array $data): bool;
    public function delete(Level $level): bool;

    public function checkCompetitionMaxLevelNumbers(int $competitionId): bool;
    public function hasTimeConflict(int $competitionId, string $startDate, int $duration, ?int $excludeLevelId = null): bool;

    public function getQuestionsCount(Level $level): int;
    public function getUnansweredQuestionsForUser(Level $level, User $user): Collection;
    public function createMultipleResponses(array $responsesData): void;
    public function assignAuditorsToUsersInPivot(Level $level, Collection $users, Collection $auditors): void;

    public function getUsersForCompetition(Competition $competition): Collection;
    public function getAuditorsForCompetition(Competition $competition): Collection;

    public function isLevelTheEarliest(Level $level): bool;
    public function isPreviousLevelAudited(Level $level): bool;
    public function areAllCompetitionLevelsAfterNow(Competition $competition, Level $currentLevel): bool;
    public function canLevelBeEdited(Level $level): bool;
    public function isLevelStillActive(Level $level): bool;
    public function canCompetitionBeEdited(Competition $competition): bool;
}
