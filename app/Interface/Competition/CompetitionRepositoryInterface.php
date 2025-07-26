<?php

namespace App\Interface\Competition;

use App\Models\Competition\Competition;
use App\Models\Admin\Admin;
interface CompetitionRepositoryInterface
{
    function findById(int|string $id, bool $withLevels = false): ?Competition;
    function update(Competition $competition , array $data): array;
    function addUsersToCompetition(Competition $competition, array $user_ids): bool;
    function addAuditorsToCompetition(Competition $competition, array $auditor_ids): bool;
}
