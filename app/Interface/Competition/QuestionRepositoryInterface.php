<?php

namespace App\Interface\Competition;

use App\Models\Competition\Level;
use App\Models\Competition\Question;
use Illuminate\Database\Eloquent\Collection;

interface QuestionRepositoryInterface
{
    /**
     * Get all questions for a specific level
     * 
     * @param Level $level
     * @return Paginator
     */
    public function getQuestionsByLevel(Level $level);

    /**
     * Find a level by ID or throw an exception if not found
     * 
     * @param int $levelId
     * @return Level
     * @throws ModelNotFoundException
     */
    public function findOrFailLevel(int $levelId);
    /**
     * Insert a batch of new questions
     * 
     * @param array $data
     * @return bool
     */
    public function insert(array $data): bool;

    /**
     * Update an existing question
     * 
     * @param Question $question
     * @param array $data
     * @return bool
     */
    public function update(Question $question, array $data): bool;
}
