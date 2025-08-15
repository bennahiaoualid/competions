<?php

namespace App\Services\GuestUsers;

use Exception;
use App\Traits\RegisterLogs;
use App\Helpers\UsersGlobalOrder;
use App\Models\GuestUsers\Choice;
use App\Contracts\FlasherInterface;
use Illuminate\Support\Facades\Auth;
use App\Models\GuestUsers\GlobalQuestion;
use App\Models\GuestUsers\GlobalResponse;
use App\Contracts\TransactionManagerInterface;
use App\Repository\GuestUsers\UserGuestRepository;

class UserGuestService
{
    use RegisterLogs;
    /**
     * The repository is injected for data access only.
     * The flasher is used for session message flashing.
     * The transaction manager is used for DB transactions.
     */
    public function __construct(
        protected UserGuestRepository $userRepository,
        protected FlasherInterface $flasher,
        protected TransactionManagerInterface $transactionManager,
    ) {
    }

    // All business logic for getting a random question
    public function getRandomQuestion(string $type = 'regular'): array
    {
        try {
            $user = Auth::user();
            
            // Get question based on type
            $question = match($type) {
                'ai' => $this->userRepository->getRandomAIQuestionForUser($user->id),
                'premium' => $this->userRepository->getRandomPremiumQuestionForUser($user->id),
                default => $this->userRepository->getRandomEligibleQuestionForUser($user->id),
            };
            
            if (!$question) {
                return ['status' => 'empty_question'];
            }
            
            // Initialize response in a transaction
            $initSuccess = $this->transactionManager->run(function () use ($question, $user) {
                $data = [
                    'score' => '0',
                    'question_id' => $question->id,
                    'user_id' => $user->id,
                    'choice_id' => null,
                    'response_duration' => '0',
                ];
                $this->userRepository->createResponse($data);
                return true;
            });
            
            if (!$initSuccess) {
                throw new Exception('Failed to initialize response');
            }
            
            // Shuffle choices in random order
            $question->choices = $question->choices->shuffle();
            
            // Store the start time in the session
            session(['start_time' => now()]);
            
            return ['status' => 'success' , 'question' => $question];
        } catch (Exception $exception) {
            $this->registerLogs('UserGuestSevice :: getRandomQuestion ',$exception);
            $this->flasher->error(__('something_went_wrong'));
            return ['status' => 'error'];
        }
    }

    // Store user response with business logic, session, and transaction management

    public function storeResponse(array $data): array
    {
        try {
            $user = Auth::user();
            $responseTime = $this->calculateResponseTime();
            $choice = Choice::find($data['choice_id']);
            $question = GlobalQuestion::find($data['question_id']);
            
            if (!$this->isCompatibleChoiceResponse($data['question_id'], $choice)) {
                throw new Exception('Choice is not compatible with question');
            }

            $response = $this->updateUserResponse($data, $choice, $question, $responseTime, $user);
            $dataResult = $this->prepareResponseData($question, $choice, $response);
            
            session()->forget(['start_time']);
            
            return ['status' => 'success', 'data_result' => $dataResult];
            
        } catch (Exception $exception) {
            $this->registerLogs('UserGuestSevice :: storeResponse ',$exception);
            $this->flasher->error(__('something_went_wrong'));
            return ['status' => 'error'];
        }
    }

    // Extracted methods for easier testing
    protected function calculateResponseTime(): float
    {
        if (!session()->has('start_time')) {
            throw new Exception('start_time not exist in session');
        }
        
        return session('start_time')->diffInUTCSeconds(now());
    }

    protected function updateUserResponse(array $data, Choice $choice, GlobalQuestion $question, float $responseTime, $user): GlobalResponse
    {
        $score = $this->calcResponseScore($choice, $question, $responseTime);
        $response = $this->userRepository->getLatestPendingResponse($data['question_id'], $user->id);

        if ($response) {
            $this->transactionManager->run(function () use ($response, $score, $responseTime, $data) {
                $this->userRepository->updateResponse($response->id, [
                    'score' => $score,
                    'response_duration' => round($responseTime, 2),
                    'choice_id' => $data['choice_id'],
                ]);
            });
            $response->refresh();
        }else{
            throw new Exception('Response not found');
        }
        
        return $response;
    }

    protected function prepareResponseData(GlobalQuestion $question, Choice $choice, GlobalResponse $response): array
    {
        return [
            'question' => $question,
            'choice' => $choice->choice_text,
            'response' => $response,
            'correct' => $choice->correct,
        ];
    }

    // Get global users order (business logic only)
    public function globalUsersOrder(): array
    {
        $users_data = UsersGlobalOrder::getUsersGlobalOrder();
        return $users_data;
    }

    // Get global user responses (business logic, error handling, and flashing)
    public function getGlobalUserResponse(): array
    {
        try {
            $user = Auth::user();
            $questions = $this->userRepository->getUserRespondedQuestionsPaginated($user->id);
            return ['status' => 'success', 'questions' => $questions];
        } catch (Exception $exception) {
            $this->registerLogs('UserGuestSevice :: getGlobalUserResponse ',$exception);
            $this->flasher->error(__('something_went_wrong'));
            return ['status' => 'error'];
        }
    }

    // Business logic helpers (private)
    private function isCompatibleChoiceResponse(int $question_id, $choice): bool
    {
        return $choice->question_id == $question_id;
    }

    private function calcResponseScore($choice, $question, int $response_time): float
    {
        $score = 0;
        if ($choice->correct) {
            if ($question->duration >= $response_time) {
                $score = $question->score - $response_time / $question->duration * $question->score / 2;
            } else {
                $score = $question->score / 2;
            }
            $score = floatval(number_format($score, 2));
        }
        return $score;
    }
}
