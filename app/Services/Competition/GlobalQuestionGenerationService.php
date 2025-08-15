<?php

namespace App\Services\Competition;

use App\Enums\AIDifficultyEnum;
use App\Enums\AISubjectEnum;
use App\Exceptions\AIQuestionGeneration\PaidServiceException;
use App\Jobs\Ai\GenerateAIQuestionJob;
use App\Models\User;
use App\Services\Payment\CoinPricingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class GlobalQuestionGenerationService
{
    public function __construct(
        protected CoinPricingService $coinPricingService
    ) {
    }

    /**
     * Generate AI question with unified response format
     */
    public function generateAIQuestion(array $params): array
    {
        try {
            // 1. Calculate cost
            $cost = $this->calculateCost($params);
            
            // 2. Check user balance
            $this->validateUserBalance($cost);
            
            // 3. Dispatch background job
            $job = dispatch(new GenerateAIQuestionJob($params, $cost, Auth::id()));
            
            // 4. Return unified success response
            return [
                'success' => true,
                'data' => [
                    'message' => __('competition.ai.question_generation_started'),
                    'cost' => $cost,
                    'estimated_time' => '10-15 seconds'
                ]
            ];
            
        } catch (PaidServiceException $e) {
            // Return specific paid service error response
            return [
                'success' => false,
                'exception_type' => 'paid_service',
                'error_type' => $e->getErrorType(),
                'message' => $e->getMessage(),
                'user_message' => $e->getMessage(),
                'context' => $e->getContext()
            ];
        } catch (\Exception $e) {
            // Return unified error response for other errors
            return [
                'success' => false,
                'exception_type' => 'global_error',
                'error_type' => 'general_error',
                'message' => 'Service temporarily unavailable',
                'user_message' => 'Service temporarily unavailable',
                'context' => [
                    'error' => $e->getMessage()
                ]
            ];
        }
    }

    /**
     * Calculate cost based on subject and difficulty
     */
    protected function calculateCost(array $params): float
    {
        $subject = AISubjectEnum::from($params['subject']);
        $difficulty = AIDifficultyEnum::from($params['difficulty']);
        
        // Base cost: 10 coins for subject + 5 coins for difficulty
        $baseCost = 10; // Fixed base cost for all subjects
        $difficultyCost = 5; // Fixed cost for difficulty selection
        
        // If random is selected, no additional cost
        $totalCost = $baseCost + $difficultyCost;
        
        return $totalCost;
    }

    /**
     * Validate user has sufficient balance
     */
    protected function validateUserBalance(float $cost): void
    {
        $user = Auth::user();
        $balance = $user->coinBalance->balance ?? 0;
        
        if ($balance < $cost) {
            throw PaidServiceException::insufficientBalance($cost, $balance, $user->id);
        }
    }

    /**
     * Get validation rules for the service
     */
    public function getValidationRules(): array
    {
        return [
            'subject' => ['required', Rule::in(AISubjectEnum::values())],
            'difficulty' => ['required', Rule::in(AIDifficultyEnum::values())],
        ];
    }

    /**
     * Get available subjects for select box
     */
    public function getAvailableSubjects(): array
    {
        $subjects = [];
        foreach (AISubjectEnum::cases() as $subject) {
            $subjects[] = [
                'value' => $subject->value,
                'text' => $subject->label()
            ];
        }
        return $subjects;
    }

    /**
     * Get available difficulties for select box
     */
    public function getAvailableDifficulties(): array
    {
        $difficulties = [];
        foreach (AIDifficultyEnum::cases() as $difficulty) {
            $difficulties[] = [
                'value' => $difficulty->value,
                'text' => $difficulty->label()
            ];
        }
        return $difficulties;
    }
} 