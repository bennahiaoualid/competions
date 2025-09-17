<?php

namespace App\Interface\Competition;

use App\Models\User;
use App\Models\Competition\Level;
use Illuminate\Support\Collection;

interface AuditRepositoryInterface
{
    /**
     * Get competitions for audit with pagination
     */
    public function getCompetitionsForAudit(array $filters = []);

    /**
     * Get user by uuid
     */
    public function getUser(string $userIdentifier): ?User;

    /**
     * Get user responses for specific level
     */
    public function getLevelQuestionsWithUserResponses(int $levelId, int $userId);

    /**
     * Check if the current admin is allowed to audit the user
     * @param Level $level
     * @param User $user
     * @return bool
     */
    public function isAdminAllowedToAuditUser(Level $level, User $user): bool;

    /**
     * Get targeted user responses
     * @param int $levelId
     * @param int $userId
     * @param array $responseIds
     * @return Collection
     */
    public function getTargetedUserResponses($levelId, $userId, $responseIds) : Collection;

    /**
     * Update response scores
     */
    public function updateResponseScores(array $responses): bool;

    /**
     * Assign admins to responses for a specific level using a single bulk update.
     * Returns the number of affected rows.
     */
    public function assignAuditorsToResponsesForLevel(int $levelId): int;

    /**
     * Bulk update responses using efficient database operations
     * 
     * @param array $bulkData Array of response data to update
     * @param array $batch_users_ids Array of users ids related to responses
     * @param array $batch_questions_ids Array of questions ids related to responses
     * @return void
     */
    public function bulkUpdateResponses(array $bulkData, $batch_users_ids, $batch_questions_ids): void;

} 