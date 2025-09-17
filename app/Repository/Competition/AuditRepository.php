<?php

namespace App\Repository\Competition;

use App\Models\User;
use App\Traits\Filterable;
use App\Traits\RegisterLogs;
use App\Helpers\PaginationHelper;
use App\Models\Competition\Level;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\Competition\Question;
use App\Models\Competition\Response;
use Illuminate\Support\Facades\Auth;
use App\Interface\Competition\AuditRepositoryInterface;

class AuditRepository implements AuditRepositoryInterface
{
    use RegisterLogs, Filterable;

    function getCompetitionsForAudit(array $filters = [])
    {
        $admin = Auth::user();
        return $this->applyFilters($admin->competitionsAudit()->with('levels'), $filters)
                    ->orderByDesc('start_date')
                    ->paginate(PaginationHelper::perPage());
    }

    public function getUser(string $userIdentifier): ?User
    {
        return User::where("anonymized_identifier",$userIdentifier)->first();
    }

    public function getLevelQuestionsWithUserResponses(int $levelId, int $userId)
    {
        return Question::where('level_id', $levelId)
            ->with(['responses' => function ($query) use ($userId) {
                $query->where('user_id', $userId);
            }])
            ->paginate(PaginationHelper::perPage());
    }

    /**
     * Check if the current admin is allowed to audit the user
     * @param Level $level
     * @param User $user
     * @return bool
     */
    public function isAdminAllowedToAuditUser(Level $level, User $user): bool
    {
        $audit_admin = DB::table('level_admin_user')
        ->where('user_id',$user->id)
        ->where('level_id',$level->id)
        ->first();
        if($audit_admin && $audit_admin->admin_id == Auth::id()){
            return true;
        }
        return false;
    }

    /**
     * Get targeted user responses
     * @param int $levelId
     * @param int $userId
     * @param array $responseIds
     * @return Collection
     */
    public function getTargetedUserResponses($levelId, $userId, $responseIds) : Collection
    {
        try{
            $responses = Response::where('user_id', $userId)
                ->where('admin_id', null)
                ->whereIn('id', array_keys($responseIds))
                ->whereHas('question', function ($query) use ($levelId) {
                    $query->where('level_id', $levelId);
                })
                ->get();
            return $responses;
        }catch(\Exception $e){
            $this->registerLogs('AuditRepository@getTargetedUserResponses', $e);
            throw $e;
        }
    }
    
    public function updateResponseScores(array $responses): bool
    {
        foreach ($responses as $response) {
            $responseModel = Response::findOrFail($response['id']);
            $responseModel->score = $response['score'];
            $responseModel->save();
        }
        return true;
    }

    /**
     * Assign admins to responses for a specific level using a single bulk update.
     * either update ai generated score response as confirmed(each response take admin_id as the admin who related to user in laeav_admin_user)
     * or set the non generated ai score admin_id to the competition creator
     * @param int $levelId the level id
     * Returns the number of affected rows.
     */
    public function assignAuditorsToResponsesForLevel(int $levelId): int
    {
        
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            // MySQL version with JOIN syntax
            $sql = "
                UPDATE responses r
                JOIN questions q ON q.id = r.question_id
                JOIN level_admin_user lau ON lau.user_id = r.user_id AND lau.level_id = q.level_id
                SET r.admin_id = lau.admin_id
                WHERE r.admin_id IS NULL AND ai_generated = 1
                AND q.level_id = ?
            ";
            
            return DB::affectingStatement($sql, [$levelId]);
        } else {
            // SQLite version with subquery
            $sql = "
                UPDATE responses 
                SET admin_id = (
                    SELECT lau.admin_id 
                    FROM questions q
                    JOIN level_admin_user lau ON lau.user_id = responses.user_id AND lau.level_id = q.level_id
                    WHERE q.id = responses.question_id AND q.level_id = ?
                )
                WHERE admin_id IS NULL 
                AND ai_generated = 1
                AND EXISTS (
                    SELECT 1 
                    FROM questions q2 
                    WHERE q2.id = responses.question_id AND q2.level_id = ?
                )
            ";
        }
            
        return DB::affectingStatement($sql, [$levelId, $levelId]);
    }

    /** @inheritDoc */
    public function bulkUpdateResponses(array $bulkData, $batch_users_ids, $batch_questions_ids): void
    {
        if (empty($bulkData)) {
            return;
        }
    
        // Properly escape IDs
        $batchUserIds = array_map('intval', $batch_users_ids);
        $batchQuestionIds = array_map('intval', $batch_questions_ids);
        $batchUserIdsStr = implode(',', $batchUserIds);
        $batchQuestionIdsStr = implode(',', $batchQuestionIds);
        
        $ai_score_generated_at = now()->toDateTimeString();
    
        // First: Update responses with valid scores
        $caseStatements = [];
        $finalScoreCases = [];
        $ids = [];
        
        foreach ($bulkData as $data) {
            $id = (int) $data['id'];
            $score = (float) $data['score'];
            $finalScore = (float) $data['final_score'];
            $ids[] = $id;
            $caseStatements[] = "WHEN {$id} THEN {$score}";
            $finalScoreCases[] = "WHEN {$id} THEN {$finalScore}";
        }
        
        if (!empty($ids)) {
            $idList = implode(',', $ids);
            $scoreCases = implode(' ', $caseStatements);
            $finalScoreCasesStr = implode(' ', $finalScoreCases);
            
            DB::statement("
                UPDATE responses 
                SET 
                    score = CASE id {$scoreCases} END,
                    final_score = CASE id {$finalScoreCasesStr} END,
                    ai_generated = true,
                    ai_score_generated_at = ?
                WHERE id IN ({$idList})
            ", [$ai_score_generated_at]);
        }
    
        // Second: Update empty responses (exclude already processed ones)
        DB::statement("
            UPDATE responses 
            SET 
                final_score = 0,
                ai_generated = true,
                ai_score_generated_at = ?
            WHERE question_id IN ({$batchQuestionIdsStr}) 
            AND user_id IN ({$batchUserIdsStr})
            AND (response_text IS NULL OR response_text = '' OR TRIM(response_text) = '')
            AND admin_id IS NULL
            AND id NOT IN (" . (empty($ids) ? '0' : implode(',', $ids)) . ")
        ", [$ai_score_generated_at]);
    }
} 