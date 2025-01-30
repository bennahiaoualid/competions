<?php

namespace App\Services\GuestUsers;


use App\Models\Competition\Question;
use App\Models\GuestUsers\GlobalQuestion;
use App\Repository\GuestUsers\GlobalQuestionRepository;

class GlobalQuestionService
{
    public function __construct(
        protected GlobalQuestionRepository $questionRepository
    ) {
    }
    public function all(): \Illuminate\View\View
    {
        return $this->questionRepository->all();
    }

    public function create( array $data): void
    {
        $this->questionRepository->create($data);
    }

    function approve(GlobalQuestion $question): void
    {
        $this->questionRepository->approve($question);
    }

    function delete(GlobalQuestion $question): void
    {
        $this->questionRepository->delete($question);
    }

}
