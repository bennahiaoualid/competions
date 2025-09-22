<?php

namespace App\Interface\User;

use App\Models\User;
use App\Models\Competition\Response;
use Illuminate\Database\Eloquent\Collection;

interface UserCompetitionRepositoryInterface
{
   
    /**
     * Get all public competitions with optional filters
     */
    public function getAllPublicCompetitions(array $filters = [], int $page, int $perPage);

    /**
     * Get user competitions with optional filters
     */
    public function getUserCompetitions(User $user, array $filters = [],  int $page, int $perPage);

    /**
     * Get unanswered questions for a level and user
     */
    public function getUnansweredQuestions(int $levelId, int $userId): Collection;

    /**
     * Create a new response record
     */
    public function createResponse(array $data): Response;

    /**
     * Update an existing response
     */
    public function updateResponse(int $question_id, array $data): bool;

    /**
     * Get user responses for a level
     */
    public function getUserLevelResponses(int $levelId, int $userId);

}
