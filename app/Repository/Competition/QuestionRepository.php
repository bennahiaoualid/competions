<?php

namespace App\Repository\Competition;

use App\Traits\RegisterLogs;
use App\Helpers\PaginationHelper;
use App\Models\Competition\Level;
use App\Models\Competition\Question;

use Illuminate\Database\Eloquent\Collection;
use App\Traits\CrudOperationNotificationAlert;
use App\Interface\Competition\QuestionRepositoryInterface;


class QuestionRepository implements QuestionRepositoryInterface
{
    use RegisterLogs;
    use CrudOperationNotificationAlert;

    /**
     * Get all questions for a specific level
     * 
     * @param int $levelId
     * @return Paginator
     */
    public function getQuestionsByLevel(Level $level)
    {
        return Question::with("level")
            ->where("level_id", $level->id)
            ->paginate(PaginationHelper::perPage());
    }

    /**
     * Find a level by ID or throw an exception if not found
     * 
     * @param int $levelId
     * @return Level
     * @throws ModelNotFoundException
     */
    public function findOrFailLevel(int $levelId)
    {
        return Level::findOrFail($levelId);
    }
    
    public function insert(array $data): bool
    {
        return Question::insert($data);
    }

    /**
     * Update an existing question
     * 
     * @param Question $question
     * @param array $data
     * @return bool
     */
    public function update(Question $question, array $data): bool
    {
        return $question->update($data);
    }
}
