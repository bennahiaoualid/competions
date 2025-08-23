<?php

namespace App\Repository\GuestUsers;

use App\Helpers\PaginationHelper;
use App\Models\GuestUsers\Choice;
use App\Models\GuestUsers\GlobalQuestion;
use App\Models\GuestUsers\GlobalResponse;
use App\Models\GuestUsers\UserPremiumQuestion;
use App\Interface\GuestUsers\UserGuestRepositoryInterface;

// This repository is responsible ONLY for data access (CRUD) operations.
// All business logic, validation, and coordination should be handled in the Service layer.
class UserGuestRepository implements UserGuestRepositoryInterface
{
    // Find a choice by ID
    public function findChoice($choiceId)
    {
        return Choice::findOrFail($choiceId);
    }

    // Create a new GlobalResponse
    public function createResponse(array $data)
    {
        return GlobalResponse::create($data);
    }

    // Get the latest response for a user and question where choice_id is null
    public function getLatestPendingResponse($questionId, $userId)
    {
        return GlobalResponse::where([
            'question_id' => $questionId,
            'user_id' => $userId,
            'choice_id' => null,
        ])->orderBy('created_at', 'desc')->first();
    }

    // Get paginated questions with user responses
    public function getUserRespondedQuestionsPaginated($userId)
    {
        return GlobalQuestion::whereHas('responses', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->with(['responses' => function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->with('choice');
            }])
            ->paginate(PaginationHelper::perPage());
    }

    // Get a question by ID
    public function findQuestion($questionId)
    {
        return GlobalQuestion::findOrFail($questionId);
    }

    // Fetch one random approved question with choices, for which the user has:
    // - never answered, OR
    // - exactly one response with score=0 (void response - choice_id = null), OR
    // - exactly two responses with score=0 (one void + one answered)
    // After two responses, the question is no longer shown (prevents cheating)
    public function getRandomEligibleQuestionForUser($userId)
    {
        $question = GlobalQuestion::select('global_questions.*')
            ->leftJoin('choices', 'global_questions.id', '=', 'choices.question_id')
            ->whereRaw("
                global_questions.id IN (
                    SELECT DISTINCT question_id
                    FROM (
                        SELECT 
                            question_id,
                            COUNT(*) as total_responses,
                            SUM(CASE WHEN score = 0 THEN 1 ELSE 0 END) as zero_score_count,
                            SUM(CASE WHEN score > 0 THEN 1 ELSE 0 END) as positive_score_count,
                            SUM(CASE WHEN choice_id IS NULL AND score = 0 THEN 1 ELSE 0 END) as void_count,
                            SUM(CASE WHEN choice_id IS NOT NULL AND score = 0 THEN 1 ELSE 0 END) as answered_zero_count
                        FROM global_responses 
                        WHERE user_id = ?
                        GROUP BY question_id
                        
                        UNION
                        
                        -- Questions with no responses
                        SELECT 
                            q2.id as question_id,
                            0 as total_responses,
                            0 as zero_score_count,
                            0 as positive_score_count,
                            0 as void_count,
                            0 as answered_zero_count
                        FROM global_questions q2
                        WHERE q2.ai = 0 
                            AND q2.approved IS NOT NULL
                            AND NOT EXISTS (
                                SELECT 1 FROM global_responses r2 
                                WHERE r2.question_id = q2.id AND r2.user_id = ?
                            )
                    ) response_summary
                    WHERE 
                        -- No responses
                        total_responses = 0
                        OR
                        -- Exactly one void response
                        (total_responses = 1 AND void_count = 1 AND answered_zero_count = 0)
                        OR
                        -- Exactly one answered response (score = 0, choice_id ≠ null)
                        (total_responses = 1 AND void_count = 0 AND answered_zero_count = 1)
                        OR
                        -- Exactly two responses with score = 0 (one void + one answered)
                        (total_responses = 2 AND zero_score_count = 2 AND positive_score_count = 0 AND answered_zero_count <= 1 AND void_count <= 1)
                )
            ", [$userId, $userId])
            ->where('global_questions.ai', false)
            ->whereNotNull('global_questions.approved')
            ->with(['choices'])
            ->inRandomOrder()
            ->first();

        if ($question) {
            return $question;
        }

        // Fallback to user's own questions if no approved questions available
        return $this->getRandomAIQuestionForUser($userId);
    }

    // Fetch one random AI question generated by the specific user
    // AI questions get an additional chance: maximum 3 responses (1 void + 2 answered with score = 0)
    public function getRandomAIQuestionForUser($userId, $questionId = null)
    {
        $query = GlobalQuestion::select('global_questions.*')
            ->leftJoin('choices', 'global_questions.id', '=', 'choices.question_id')
            ->whereRaw($this->getRawSqlForAiQuestions(), [$userId, $userId, $userId])
            ->where('global_questions.ai', true)
            ->where('global_questions.user_id', $userId);

        if ($questionId) {
            $query->where('global_questions.id', $questionId);
        }else{
            $query->inRandomOrder();
        }

        return $query->with(['choices'])->first();
    }

    // Fetch one random premium AI question (after 48 hours exclusivity)
    public function getRandomPremiumQuestionForUser($userId)
    {
        return GlobalQuestion::select('global_questions.*')
            ->leftJoin('choices', 'global_questions.id', '=', 'choices.question_id')
            ->whereRaw("
                global_questions.id IN (
                    SELECT DISTINCT question_id
                    FROM (
                        SELECT 
                            question_id,
                            COUNT(*) as total_responses,
                            SUM(CASE WHEN score = 0 THEN 1 ELSE 0 END) as zero_score_count,
                            SUM(CASE WHEN score > 0 THEN 1 ELSE 0 END) as positive_score_count,
                            SUM(CASE WHEN choice_id IS NULL AND score = 0 THEN 1 ELSE 0 END) as void_count,
                            SUM(CASE WHEN choice_id IS NOT NULL AND score = 0 THEN 1 ELSE 0 END) as answered_zero_count
                        FROM global_responses 
                        WHERE user_id = ?
                        GROUP BY question_id
                        
                        UNION
                        
                        -- Questions with no responses
                        SELECT 
                            q2.id as question_id,
                            0 as total_responses,
                            0 as zero_score_count,
                            0 as positive_score_count,
                            0 as void_count,
                            0 as answered_zero_count
                        FROM global_questions q2
                        WHERE q2.approved IS NOT NULL
                            AND q2.ai = true
                            AND q2.created_at <= ?
                            AND NOT EXISTS (
                                SELECT 1 FROM global_responses r2 
                                WHERE r2.question_id = q2.id AND r2.user_id = ?
                            )
                    ) response_summary
                    WHERE 
                        -- No responses
                        total_responses = 0
                        OR
                        -- Exactly one void response
                        (total_responses = 1 AND void_count = 1 AND answered_zero_count = 0)
                        OR
                        -- Exactly two responses with score = 0 (one void + one answered)
                        (total_responses = 2 AND zero_score_count = 2 AND positive_score_count = 0 AND answered_zero_count <= 1 AND void_count <= 1)
                )
            ", [$userId, now()->subHours(48), $userId])
            ->whereNotNull('global_questions.approved')
            ->where('global_questions.ai', true)
            ->where('global_questions.created_at', '<=', now()->subHours(48)) // After 48 hours
            ->whereDoesntHave('premiumOwners', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->with(['choices'])
            ->inRandomOrder()
            ->first();
    }

    // Create premium question ownership record
    public function createPremiumQuestionOwnership(int $userId, int $questionId): void
    {
        UserPremiumQuestion::create([
            'user_id' => $userId,
            'global_question_id' => $questionId,
        ]);
    }

    // Get count of eligible AI questions for a user
    public function getEligibleAIQuestionCountForUser(int $userId): int
    {
        return GlobalQuestion::whereRaw($this->getRawSqlForAiQuestions(), [$userId, $userId, $userId])
        ->where('ai', true)
        ->where('user_id', $userId)
        ->count();
    }

    private function getRawSqlForAiQuestions():string
    {
        return "
                global_questions.id IN (
                    SELECT DISTINCT question_id
                    FROM (
                        SELECT 
                            question_id,
                            COUNT(*) as total_responses,
                            SUM(CASE WHEN score = 0 THEN 1 ELSE 0 END) as zero_score_count,
                            SUM(CASE WHEN score > 0 THEN 1 ELSE 0 END) as positive_score_count,
                            SUM(CASE WHEN choice_id IS NULL AND score = 0 THEN 1 ELSE 0 END) as void_count,
                            SUM(CASE WHEN choice_id IS NOT NULL AND score = 0 THEN 1 ELSE 0 END) as answered_zero_count
                        FROM global_responses 
                        WHERE user_id = ?
                        GROUP BY question_id
                        
                        UNION
                        
                        -- Questions with no responses
                        SELECT 
                            q2.id as question_id,
                            0 as total_responses,
                            0 as zero_score_count,
                            0 as positive_score_count,
                            0 as void_count,
                            0 as answered_zero_count
                        FROM global_questions q2
                        WHERE q2.ai = true 
                            AND q2.user_id = ?
                            AND NOT EXISTS (
                                SELECT 1 FROM global_responses r2 
                                WHERE r2.question_id = q2.id AND r2.user_id = ?
                            )
                    ) response_summary
                        WHERE 
                            -- No responses
                            total_responses = 0
                            OR
                            -- Exactly one void response
                            (total_responses = 1 AND void_count = 1 AND answered_zero_count = 0)
                            OR
                            -- Exactly one answered response (score = 0, choice_id ≠ null)
                            (total_responses = 1 AND void_count = 0 AND answered_zero_count = 1)
                            OR
                            -- Exactly two responses with score = 0
                            (total_responses = 2 AND zero_score_count = 2 AND 
                                (
                                    (answered_zero_count = 2 AND void_count = 0)
                                    OR (answered_zero_count = 1 AND void_count = 1)
                                )
                            )
                            OR
                            -- Exactly three responses with score = 0 (one void + two answered) - AI questions get extra chance
                            (total_responses = 3 AND zero_score_count = 3 AND answered_zero_count = 2 AND void_count = 1)
                )
            ";
    }

}
