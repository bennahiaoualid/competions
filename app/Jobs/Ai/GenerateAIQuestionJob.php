<?php

namespace App\Jobs\Ai;

use App\Enums\AISubjectEnum;
use App\Enums\UserLocaleEnum;
use App\Exceptions\AIQuestionGeneration\LLMCodeException;
use App\Exceptions\AIQuestionGeneration\LLMConnectionException;
use App\Exceptions\AIQuestionGeneration\QuestionGenerationProcessException;
use Illuminate\Bus\Queueable;
use App\Enums\AIDifficultyEnum;
use App\Models\GuestUsers\Choice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use App\Services\LLM\LLMHandlerFactory;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\GuestUsers\GlobalQuestion;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Events\PaidServices\AIQuestionGenerated;
use App\Events\PaidServices\AIQuestionGenerationFailed;

class GenerateAIQuestionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120; // 2 minutes timeout
    public $tries = 3; // Retry 3 times

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected array $params,
        protected float $cost,
        protected int $userId
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(LLMHandlerFactory $handlerFactory): void
    {
        try {
            DB::transaction(function () use ($handlerFactory) {
            // 1. Get default LLM service 
                $llmService = $handlerFactory->getDefault();
                
                // 2. Generate question using LLM
                $prompt = $this->buildPrompt($this->params);
                $response = $llmService->generate($prompt['prompt']);
                
                if (!$response->isSuccess()) {
                    throw LLMCodeException::responseProcessingError(
                        'LLM generation failed: ' . $response->getError(),
                        ['response' => $response->getError()]
                    );
                }
                
                // 3. Parse LLM response and create question
                $questionData = $this->parseLLMResponse($response->getContent());
                
                // 4. Store question in database (simplified for now)
                $question = $this->storeDataInDb($questionData);
                
                // 5. Fire success event for coin deduction
                DB::afterCommit(function () use ($question) {
                    
                    event(new AIQuestionGenerated($question, $this->userId, $this->cost));
                    Log::info('AI question generated successfully', [
                        'question_id' => $question->id,
                        'user_id' => $this->userId,
                        'cost' => $this->cost
                    ]);
                });
                
            });
            
        } catch (QuestionGenerationProcessException $e) {
            // Exception already logged itself! No manual logging needed.
            // Fire failure event
            event(new AIQuestionGenerationFailed($this->userId, $this->cost, $e->getMessage(), $this->params, 'question_generation_process'));
            
            throw $e;
        } catch (LLMCodeException $e) {
            // Exception already logged itself! No manual logging needed.
            // Fire failure event
            event(new AIQuestionGenerationFailed($this->userId, $this->cost, $e->getMessage(), $this->params, 'llm_code'));
            
            throw $e;
        } catch (LLMConnectionException $e) {
            // Exception already logged itself! No manual logging needed.
            // Fire failure event
            event(new AIQuestionGenerationFailed($this->userId, $this->cost, $e->getUserMessage(), $this->params, 'llm_connection'));
            
            throw $e;
        } catch (\Exception $e) {
            // Log general errors that don't have custom exception classes
            Log::error('AI question generation failed with general error', [
                'user_id' => $this->userId,
                'params' => $this->params,
                'error' => $e->getMessage()
            ]);
            
            // Fire failure event
            event(new AIQuestionGenerationFailed($this->userId, $this->cost, $e->getMessage(), $this->params, 'general_error'));
            
            throw $e;
        }
    }

    /**
     * Build prompt for LLM with enhanced structure and guidelines
     */
    protected function buildPrompt(array $params): array
    {
        $maxTokens = $params['max_tokens'] ?? 350; // Optimal for quiz content

        $subject = AISubjectEnum::tryFrom($params['subject']) ?? AISubjectEnum::RANDOM;
        $choicesCount = max(2, min(5, $params['choices_count'] ?? 4)); // Constrain between 2-5
        
        $difficulty = AIDifficultyEnum::tryFrom($params['difficulty']) ?? AIDifficultyEnum::RANDOM;
        
        $systemMessage = $this->getSystemMessag();

        $difficultyGuidelines = $difficulty->getGuidelines();
        $subjectContext = $subject->getContext($difficulty);
        
        $prompt = $this->getPromptFormat($systemMessage, $subject, $difficulty, $choicesCount, $difficultyGuidelines, $subjectContext);
        return [
            'prompt' => $prompt,
            'max_tokens' => $maxTokens,
            'temperature' => 0.7,
            'top_p' => 0.9
        ];
    }

    /**
     * Parse LLM response
     */
    protected function parseLLMResponse(string $content): array
    {
        // First: try to extract from markdown code blocks (any language)
        if (preg_match('/```(?:json)?\s*(.*?)\s*```/s', $content, $matches)) {
            $jsonContent = $matches[1];
            $decoded = json_decode($jsonContent, true);
            
            if (json_last_error() === JSON_ERROR_NONE && 
                $this->validateParsedData($decoded)) {
                return $decoded;
            }
        }
        
        // Second: try to parse the entire content as JSON
        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && 
            $this->validateParsedData($decoded)) {
            return $decoded;
        }
        
        // Third: try to clean the content and parse (remove markdown code blocks)
        $cleanedContent = preg_replace('/```(?:json)?\s*|\s*```/', '', $content);
        $decoded = json_decode($cleanedContent, true);
        if (json_last_error() === JSON_ERROR_NONE && 
            $this->validateParsedData($decoded)) {
            return $decoded;
        }
        
        // If all parsing attempts fail, throw a process exception
        throw QuestionGenerationProcessException::formatContentError(
            'Failed to parse LLM response. Expected valid JSON with question, choices, correct_answer, explanation, and duration.',
            [
                'content_preview' => substr($content, 0, 200) . '...',
                'content_length' => strlen($content)
            ]
        );
    }

    /**
     * Validate parsed data has all required fields
     */
    protected function validateParsedData(array $data): bool
    {
        $requiredFields = ['question', 'choices', 'correct_answer', 'explanation', 'duration'];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                return false;
            }
        }
        
        // Validate duration is numeric and within reasonable range
        if (!is_numeric($data['duration'])) {
            return false;
        }
        
        // Validate explanation is not empty
        if (empty(trim($data['explanation']))) {
            return false;
        }
        
        return true;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('AI question generation job failed permanently', [
            'user_id' => $this->userId,
            'params' => $this->params,
            'error' => $exception->getMessage()
        ]);
        
        // Fire failure event
        event(new AIQuestionGenerationFailed($this->userId, $this->cost, $exception->getMessage(), $this->params));
    }

    /**
     * Get System message
     */
    public function getSystemMessag() : string {
        $subject = AISubjectEnum::tryFrom($this->params['subject']) ?? AISubjectEnum::RANDOM;
        $languageConstraint = UserLocaleEnum::getAiPromptDescriptionForSubject($subject->value, $this->getUserLocale());
        
        return "You are an expert educational content creator. Create high-quality multiple choice questions that: are factually accurate, have one correct answer, include plausible distractors, use clear language for the difficulty level, and test understanding not memorization. " . 
               $languageConstraint;
    }

    /**
     * get prompot format
     */
    private function getPromptFormat($systemMessage, $subject, $difficulty,$choicesCount, $difficultyGuidelines, $subjectContext)  {
        return "{$systemMessage}

        Generate a multiple choice question for **{$subject->value}** at **{$difficulty->label()}** level with **{$choicesCount}** answer choices.
        
        Guidelines: {$difficultyGuidelines}
        Context: {$subjectContext}
        
        CRITICAL: You must respond with ONLY raw JSON text. Do NOT wrap in markdown, do NOT add code blocks, do NOT add explanations.

        Required format:
        {
            \"question\": \"[Clear, specific question text]\",
            \"choices\": [\"Choice A\", \"Choice B\", \"Choice C\", \"Choice D\"],
            \"correct_answer\": \"[Exact match of one choice]\",
            \"explanation\": \"[Clear explanation of why the correct answer is right. 2-3 sentences max]\",
            \"duration\": [Estimated time in seconds for an average user to answer correctly]
        }
        Requirements:
        - Question: Clear, specific, tests understanding
        - Choices: {$choicesCount} distinct, plausible options
        - Correct answer: Must exactly match one choice text
        - Explanation: Brief, educational explanation of the correct answer
        - Duration: Realistic time estimate based on question complexity
        - No markdown, no explanations, just JSON
        Ensure all choices are distinct, plausible, and the question tests meaningful understanding.";
                
    }

    /**
     * store data in db
     */
    private function storeDataInDb($questionData): GlobalQuestion {
        try {
            $question = GlobalQuestion::create([
                'question_text' => $questionData['question'],
                'explanation' => $questionData['explanation'],
                'score' => 10,
                'duration' => $questionData['duration'],
                'explanation' => $questionData['explanation'],
                'text_direction' => $this->getUserLocale()->getDirection(),
                'admin_id' => null,
                'approved' => null,
                'ai' => true,
                'user_id' => $this->userId
            ]);
            
            $choices = [];
            foreach ($questionData['choices'] as $choice) {
                $choices[] = [
                    'question_id' => $question->id,
                    'choice_text' => $choice,
                    'correct' => $choice === $questionData['correct_answer']
                ];
            }


            Choice::insert($choices);

            return $question;
            
        } catch (\Exception $e) {
            throw QuestionGenerationProcessException::dataStorageError(
                'Failed to store question data in database',
                [
                    'question_data' => $questionData,
                    'database_error' => $e->getMessage()
                ]
            );
        }
    }

    /**
     * Get user locale for text direction and AI prompts
     */
    private function getUserLocale(): UserLocaleEnum
    {
        return UserLocaleEnum::getDefault();
    }
} 