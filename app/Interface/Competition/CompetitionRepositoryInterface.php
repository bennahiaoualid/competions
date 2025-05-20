<?php

namespace App\Interface\Competition;

use App\Models\Competition\Competition;

interface CompetitionRepositoryInterface
{
    function findById(int|string $id): ?Competition;
    function create(array $data): Competition;
    function update(Competition $competition , array $data): array;
    function delete(Competition $competition): bool;
    function getCompetitionWithUsers(int|string $competition_id): ?Competition;
    function removeUserFromCompetition(Competition $competition, int $user_id): bool;
    function addUsersToCompetition(Competition $competition, array $user_ids): bool;
    function getCompetitionWithAuditors(int|string $competition_id): ?Competition;
    function addAuditorsToCompetition(Competition $competition, array $auditor_ids): bool;
    function removeAuditorFromCompetition(Competition $competition, int $auditor_id): bool;
    function activate(Competition $competition): bool;
}
