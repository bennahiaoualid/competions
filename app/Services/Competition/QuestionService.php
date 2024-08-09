<?php

namespace App\Services\Competition;

use App\Interface\Competition\QuestionRepositoryInterface;
use App\Models\Competition\Question;

class QuestionService
{
    public function __construct(
        protected QuestionRepositoryInterface $questionRepository
    ) {
    }
    public function all($level_id){
        return $this->questionRepository->all($level_id);
    }

    public function create( array $data){
        return $this->questionRepository->create($data);
    }

    public function update(Question $question, array $data){
        return $this->questionRepository->update($question, $data);
    }

}
