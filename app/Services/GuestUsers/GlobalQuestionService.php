<?php

namespace App\Services\GuestUsers;

use App\Models\GuestUsers\GlobalQuestion;
use App\Contracts\FlasherInterface;
use App\Contracts\TransactionManagerInterface;
use App\Traits\RegisterLogs;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Exception;

/**
 * Service for GlobalQuestion business logic and coordination.
 * This class handles all business logic, validation, and coordinates between repositories.
 */
class GlobalQuestionService
{
    use RegisterLogs;

    public function __construct(
        protected FlasherInterface $flasher,
        protected TransactionManagerInterface $transactionManager
    ) {
    }

    /**
     * Create a new global question with business logic validation
     * @param array $data
     * @return void
     */
    public function create(array $data): void
    {
        try {
            // Business logic validation
            if (!$this->validateChoices($data)) {
                $this->flasher->error(__('messages.validation.not_allow.global_question_choices'));
                return;
            }

            $this->transactionManager->run(function () use ($data) {
                // Prepare question data
                $questionData = $this->prepareQuestionData($data);
                $choicesData = $data['choice'];

                // Create question and choices
                $question = GlobalQuestion::create($questionData);
        
                foreach ($choicesData as $index => $choiceData) {
                    $question->choices()->create([
                        "choice_text" => $choiceData,
                        "correct" => $index == 0, // First choice is always correct
                    ]);
                }
            });

            $this->flasher->crudSuccess('saved');

        } catch (Exception $exception) {
            $this->registerLogs('Global Questions creation error: ', $exception);
            $this->flasher->crudFailure('saved');
        }
    }

    /**
     * Approve a global question with business logic validation
     * @param GlobalQuestion $question
     * @return void
     */
    function approve(GlobalQuestion $question): void
    {
        try {
            // Business logic validation
            if (!$this->canApproveQuestion($question)) {
                $this->flasher->error(__('messages.validation.not_allow.not_authorized'));
                return;
            }

            $this->transactionManager->run(function () use ($question) {
                $question->approved = Auth::id();
                return $question->save();
            });

            $this->flasher->crudSuccess('approved');

        } catch (Exception $exception) {
            $this->registerLogs('Global Questions approve error: ', $exception);
            $this->flasher->crudFailure('approved');
        }
    }

    /**
     * Delete a global question with business logic validation
     * @param GlobalQuestion $question
     * @return void
     */
    function delete(GlobalQuestion $question): void
    {
        try {
            // Business logic validation
            if (!$question->canDelete()) {
                $this->flasher->error(__('messages.validation.not_allow.not_authorized'));
                return;
            }

            $this->transactionManager->run(function () use ($question) {
                $question->delete();;
            });

            $this->flasher->crudSuccess('deleted');

        } catch (Exception $exception) {
            $this->registerLogs('Global Questions delete error: ', $exception);
            $this->flasher->crudFailure('deleted');
        }
    }

    /**
     * Validate choices array for business rules
     * @param array $data
     * @return bool
     */
    private function validateChoices(array $data): bool
    {
        return array_key_exists('choice', $data) && 
                count($data['choice']) >= 2 && 
                count($data['choice']) <= 5;
    }

    /**
     * Check if current user can approve the question
     * @param GlobalQuestion $question
     * @return bool
     */
    private function canApproveQuestion(GlobalQuestion $question): bool
    {
        $user = Auth::user();
        return $user->hasRole(['super_admin', 'owner'], 'admin') && 
               $question->approved === null;
    }

    /**
     * Prepare question data for creation
     * @param array $data
     * @return array
     */
    private function prepareQuestionData(array $data): array
    {
        $user = Auth::user();
        $approved = $user->hasRole(['super_admin', 'owner'], 'admin') ? $user->id : null;

        return [
            'question_text' => $data['question_text'],
            'explanation' => $data['explanation'],
            'text_direction' => $data['txt_direction'],
            'score' => $data['score'],
            'duration' => $data['duration'],
            'admin_id' => $user->id,
            'approved' => $approved,
        ];
    }
}
