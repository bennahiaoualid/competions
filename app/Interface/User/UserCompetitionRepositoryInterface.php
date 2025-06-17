<?php

namespace App\Interface\User;

use App\Models\User;
use Illuminate\View\View;
use App\Models\Competition\Level;
use App\Models\Competition\Question;
use App\Models\Competition\Response;
use App\Models\Competition\Competition;
use Illuminate\Database\Eloquent\Collection;

interface UserCompetitionRepositoryInterface
{
   
    /**
     * Get all public competitions with optional filters
     */
    public function getAllPublicCompetitions(array $filters = []): Collection;

    /**
     * Get user competitions with optional filters
     */
    public function getUserCompetitions(User $user, array $filters = []): Collection;

    /**
     * Get competition by ID with its levels
     */
    public function getCompetitionWithLevels(int $competitionId): Competition;

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
    public function getUserLevelResponses(int $levelId, int $userId): Collection;

}
