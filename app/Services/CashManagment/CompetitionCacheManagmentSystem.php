<?php

namespace App\Services\CashManagment;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class CompetitionCacheManagmentSystem
{


    public function getUsersCountForAdminAuditing($adminId) 
    {
        $cacheTags = $this->getCacheTags('users_responses_auditing_system', $adminId);
        $cacheDuration = $this->getCacheDuration('getUsersCountForAdminAuditing');
        $cacheKey = $this->buildCacheKey('getUsersCountForAdminAuditing', ['adminId' => $adminId]);
        
        // Query to get both counts: manual audit needed and AI confirmation needed
        return Cache::tags($cacheTags)
            ->remember($cacheKey, $cacheDuration, function () use ($adminId) {
                $results = DB::table('users')
                    ->join('level_admin_user', 'users.id', '=', 'level_admin_user.user_id')
                    ->join('responses', 'users.id', '=', 'responses.user_id')
                    ->join('questions', 'responses.question_id', '=', 'questions.id')
                    ->where('level_admin_user.admin_id', $adminId)
                    ->whereColumn('level_admin_user.level_id', 'questions.level_id')
                    ->whereNull('responses.admin_id')
                    ->selectRaw('
                        COUNT(DISTINCT CASE WHEN responses.ai_generated = false THEN users.id END) as needs_manual_audit,
                        COUNT(DISTINCT CASE WHEN responses.ai_generated = true THEN users.id END) as needs_ai_confirmation
                    ')
                    ->first();

                return [
                    'needs_manual_audit' => $results->needs_manual_audit ?? 0,
                    'needs_ai_confirmation' => $results->needs_ai_confirmation ?? 0
                ];
        });
    }

        /**
     * Invalidate user coin transactions cache
     */
    public function invalidateUsersAuditingInfo($adminId): void
    {
        $cacheTags = $this->getCacheTags('users_responses_auditing_system', $adminId);
        Cache::tags($cacheTags)->flush();
    }

        /**
     * Build cache key based on method and parameters
     */
    private function buildCacheKey($method, $params): string
    {
        $baseKey = "competition_cache_{$method}";
        
        if (empty($params)) {
            return $baseKey;
        }

        // Create a consistent hash from parameters
        $paramString = '';
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                // Sort arrays to ensure consistent keys for same filter combinations
                ksort($value);
                $paramString .= $key . '_' . md5(serialize($value));
            } else {
                $paramString .= $key . '_' . $value;
            }
        }

        return $baseKey . '_' . md5($paramString);
    }

    /**
     * Get cache tags for organized cache invalidation
     */
    private function getCacheTags($entity, $id = null): array
    {
        $tags = [$entity];
        
        if ($id !== null) {
            $tags[] = $entity . '_' . $id;
        }

        return $tags;
    }

    /**
     * Get cache duration based on method type
     */
    private function getCacheDuration($method): int
    {
        $cacheDurations = [
            'getUsersCountForAdminAuditing' => 3600 , // 1 hours
        ];

        return $cacheDurations[$method] ?? 600; // Default 10 minutes
    }
}