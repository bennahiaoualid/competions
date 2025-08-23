<?php

namespace App\Services\GuestUsers;

use Exception;
use App\Models\User;
use App\Traits\RegisterLogs;
use App\Helpers\UsersGlobalOrder;
use App\Models\GuestUsers\Choice;
use App\Contracts\FlasherInterface;
use Illuminate\Support\Facades\Auth;
use App\Enums\CoinTransactionTypeEnum;
use App\Services\SystemSettingService;
use App\Models\GuestUsers\GlobalQuestion;
use App\Models\GuestUsers\GlobalResponse;
use App\Contracts\TransactionManagerInterface;
use App\Events\GuestUsers\ResponseStorageFailed;
use App\Services\Payment\CoinTransactionService;
use App\Repository\GuestUsers\UserGuestRepository;
use App\Exceptions\GuestUsers\SessionStartTimeException;
use App\Exceptions\AIQuestionGeneration\PaidServiceException;
use App\Exceptions\GuestUsers\IncompatibleChoiceResponseException;

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
        protected SystemSettingService $systemSettingService,
        protected CoinTransactionService $coinTransactionService,
    ) {
    }

    // All business logic for getting a random question
    public function getRandomQuestion(string $type = 'regular', $questionId = null): array
    {
        try {
            $user = Auth::user();
            
            // Get question based on type
            $question = match($type) {
                'ai' => $this->userRepository->getRandomAIQuestionForUser($user->id, $questionId),
                'premium' => $this->userRepository->getRandomPremiumQuestionForUser($user->id),
                default => $this->userRepository->getRandomEligibleQuestionForUser($user->id),
            };
            
            if (!$question) {
                return ['status' => 'empty_question', 'type' => $type];
            }
            
            
            // Initialize response in a transaction
            $initSuccess = $this->transactionManager->run(function () use ($question, $user, $type) {
                if ($type === 'premium') {
                    $this->handlePremiumQuestionOwnership($user, $question);
                }
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
            session(['start_time' => now(), 'question_type' => $type]);
            
            return ['status' => 'success' , 'question' => $question, 'type' => $type];
        } catch (PaidServiceException $exception) {
            $this->flasher->error(__('exceptions.insufficient_balance'));
            return ['status' => 'error', 'type' => $type];
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
            $question = GlobalQuestion::findOrFail($data['question_id']);
            
            $this->isCompatibleChoiceResponse($data['question_id'], $choice);
            
            $type = session('question_type');
            $dataResult = $this->transactionManager->run(function () use ($data, $choice, $question, $responseTime, $user, $type) {
                $response = $this->updateUserResponse($data, $choice, $question, $responseTime, $user);
                $dataResult = $this->prepareResponseData($question, $choice, $response,$user, $type);
                session()->forget(['start_time','question_type']);
                return $dataResult;
            });
            
            return ['status' => 'success', 'data_result' => $dataResult, 'type' => $type];
            
        } catch (IncompatibleChoiceResponseException $exception) {
            $this->registerLogs('UserGuestSevice :: storeResponse ',$exception);
            $this->flasher->error($exception->getMessage());
            return ['status' => 'error'];
        } catch (SessionStartTimeException $exception) {
            $this->registerLogs('UserGuestSevice :: storeResponse ',$exception);
            $this->flasher->error($exception->getMessage());
            return ['status' => 'error'];
        } catch (Exception $exception) {
            // Fire event to cleanup the void response
            event(new ResponseStorageFailed($data['question_id'], $user->id));
            
            $this->registerLogs('UserGuestSevice :: storeResponse ',$exception);
            $this->flasher->error(__('something_went_wrong'));
            return ['status' => 'error'];
        }
    }

    
    protected function calculateResponseTime(): float
    {
        if (!session()->has('start_time')) {
            throw SessionStartTimeException::missingStartTime();
        }
        
        return session('start_time')->diffInUTCSeconds(now());
    }

    protected function updateUserResponse(array $data, Choice $choice, GlobalQuestion $question, float $responseTime, $user): GlobalResponse
    {
        $score = $this->calcResponseScore($choice, $question, $responseTime);
        $response = $this->userRepository->getLatestPendingResponse($data['question_id'], $user->id);

        if ($response) {
            $response->update([
                'score' => $score,
                'response_duration' => round($responseTime, 2),
                'choice_id' => $data['choice_id'],
            ]);
        }else{
            throw new Exception('Response not found');
        }
        
        return $response;
    }

    protected function prepareResponseData(GlobalQuestion $question, Choice $choice, GlobalResponse $response, User $user , $type): array
    {
        $responses_count = $user->globalResponses()
        ->where('question_id', $question->id)
        ->count();
        $allowed_responses_count = $type === 'ai' ? 3 : 2;
        if(($choice->correct || $responses_count == $allowed_responses_count) && $question->explanation){
            $show_explanation = true;
        }else{
            $show_explanation = false;
        }
        return [
            'question' => $question,
            'choice' => $choice->choice_text,
            'response' => $response,
            'correct' => $choice->correct,
            'show_explanation' => $show_explanation,
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

    public function getAIQuestionEligibileCount(): int
    {
        $user = Auth::user();
        return $this->userRepository->getEligibleAIQuestionCountForUser($user->id);
    }

    // Handle premium question ownership when user gets a random premium question
    private function handlePremiumQuestionOwnership(User $user, GlobalQuestion $question): void
    {
        // Calculate premium cost 
        $baseCost = $this->systemSettingService->getValueAsFloat('global_question_generating_cost');
        $premiumCostPercentage = $this->systemSettingService->getValueAsFloat('global_question_premium_cost_percentage');
        $premiumCost = (int)($baseCost * ($premiumCostPercentage / 100));
        
        // Deduct coins from user balance
        $coinBalance = $user->coinBalance;

        if (!$coinBalance || !$coinBalance->spendCoins($premiumCost)) {
            throw new PaidServiceException('Insufficient coins for premium question', PaidServiceException::ERROR_INSUFFICIENT_BALANCE);
        }
        
        // Create transaction record
        $this->coinTransactionService->createPremiumQuestionPurchaseTransaction($user, $premiumCost);
        
        // Create ownership record
        $this->userRepository->createPremiumQuestionOwnership($user->id, $question->id);
    }

    // Business logic helpers (private)
    private function isCompatibleChoiceResponse(int $question_id, $choice): void
    {
        if($choice?->question_id !== $question_id)
        {
            throw IncompatibleChoiceResponseException::choiceNotBelongsToQuestion($question_id);
        }
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
