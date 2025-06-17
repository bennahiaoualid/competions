<?php

namespace App\Interface\Competition;

use App\Models\Competition\Level;
use App\Models\User;

interface AuditRepositoryInterface
{
    /**
     * Get competitions for audit with pagination
     */
    public function getCompetitionsForAudit(array $filters = []);

    /**
     * Get level by ID
     */
    public function getLevel(int $levelId): Level;

    /**
     * Get user by ID
     */
    public function getUser(int $userId): User;

    /**
     * Get questions for a level
     */
    public function getLevelQuestions(int $levelId): array;

    /**
     * Get user responses for specific questions
     */
    public function getUserResponses(int $userId, array $questionIds): array;

    /**
     * Update response scores
     */
    public function updateResponseScores(array $responses): bool;

    function auditUsers($level_id);
    function auditUserResponses($level_id, $user_identifier);
    function submitAudit(array $responses, $user_id, $level_id);
} 