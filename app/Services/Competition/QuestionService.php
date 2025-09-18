<?php

namespace App\Services\Competition;

use Exception;
use App\Traits\RegisterLogs;
use App\Helpers\PaginationHelper;
use App\Models\Competition\Level;
use App\Contracts\FlasherInterface;
use App\Models\Competition\Question;
use App\Contracts\TransactionManagerInterface;

class QuestionService
{
    use RegisterLogs;

    public function __construct(
        protected TransactionManagerInterface $transactionManager,
        protected FlasherInterface $flasher
    ) {
    }

    /**
     * Get all questions for a specific level
     * 
     * @param Level $level The level to get the questions for.
     */
    public function all(Level $level)
    {
        return Question::with("level")
            ->where("level_id", $level->id)
            ->paginate(PaginationHelper::perPage());
    }

    /**
     * Create new questions for a level
     * 
     * @param array $data Question data including level_id, question_text, duration, and max_score
     * @param Level $level The level to store the questions for.
     * @return bool
     */
    public function create(array $data, Level $level): bool
    {
        try {
            if(!isset($data['question_text'])){
                return false;
            }
            
            if(!$level->canEditQuestion()){
                $this->flasher->error(
                    __('messages.validation.not_allow.question_update'),
                );
                return false;
            }
            
            if($level->status != Level::STATUS_PENDING){
                $this->flasher->error(
                    __('messages.validation.not_allow.active_level_update'),
                );
                return false;
            }

            if(!$this->validateQuestionsNumber($level,count($data['question_text']))){
                $this->flasher->error(
                    __('messages.validation.not_allow.question_update_max_number',['number' => $level->questions_number]),
                );
                return false;
            }

            $result = $this->transactionManager->run(function () use ($data, $level) {
                $questions = collect($data['question_text'])->map(function ($text, $i) use ($data, $level) {
                    return [
                        'question_text' => $text,
                        'duration' => $data['duration'][$i],
                        'level_id' => $level->id,
                        'max_score' => $data['max_score'][$i],
                        'perfect_response' => $data['perfect_response'][$i],

                    ];
                })->toArray();
                
                Question::insert($questions);
                return true;
            });

            $this->flasher->crudSuccess("saved");
            return $result;
        } catch (Exception $exception) {
            $this->registerLogs('Questions creation error: ', $exception);
            $this->flasher->crudFailure("saved");
            return false;
        }
    }

    /**
     * Update an existing question
     * 
     * @param Question $question Question to update
     * @param array $data Updated question data
     * @return bool
     */
    public function update(Question $question, array $data): bool
    {
        try {
            $level = $question->level;

            if(!$level){
                return false;
            }
            
            if(!$level->canEditQuestion()){
                $this->flasher->error(
                    __('messages.validation.not_allow.question_update'),
                );
                return false;
            }
            if($level->status != Level::STATUS_PENDING){
                $this->flasher->error(
                    __('messages.validation.not_allow.active_level_update'),
                );
                return false;
            }

            $result = $this->transactionManager->run(function () use ($question, $data) {
                return $question->update($data);
            });

            $this->flasher->crudSuccess("saved");
            return $result;
        } catch (Exception $exception) {
            $this->registerLogs('Questions update error: ', $exception);
            $this->flasher->crudFailure("saved");
            return false;
        }
    }

    /**
     * Validate the number of questions to be created
     * 
     * @param Level $level
     * @param int $questions_count
     * @return bool
     */
    private function validateQuestionsNumber($level,$questions_count): bool
    {
        $questions_number = $level->questions_number;
        $existing_questions_count = $level->questions->count();
        if($questions_number < $existing_questions_count + $questions_count){
            return false;
        }
        return true;
    }
}   
