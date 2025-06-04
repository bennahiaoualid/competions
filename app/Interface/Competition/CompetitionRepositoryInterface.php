<?php

namespace App\Interface\Competition;

use App\Models\Competition\Competition;

interface CompetitionRepositoryInterface
{
    function findById(int|string $id, bool $withLevels = false): ?Competition;
    function create(array $data): Competition;
    function update(Competition $competition , array $data): array;
    function delete(Competition $competition): bool;
    function removeUserFromCompetition(Competition $competition, int $user_id): bool;
    function addUsersToCompetition(Competition $competition, array $user_ids): bool;
    function addAuditorsToCompetition(Competition $competition, array $auditor_ids): bool;
    function removeAuditorFromCompetition(Competition $competition, int $auditor_id): bool;
    function activate(Competition $competition): bool;
}
