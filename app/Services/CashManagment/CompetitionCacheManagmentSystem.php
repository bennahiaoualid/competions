<?php

namespace App\Services\CashManagment;

use App\Helpers\CompetitionsOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Competition\Competition;
use Illuminate\Database\Eloquent\Model;
use App\Interface\User\UserCompetitionRepositoryInterface;

class CompetitionCacheManagmentSystem
{
    public function __construct(protected UserCompetitionRepositoryInterface $userCompetitionRepository)
    {
        
    }

    /**
     * Get cached detail order
     * @param string $key
     * @param Model $model level or competition object
     * @param bool $limit
     * @param bool $isCompetition
     * @param int $competitionId needed in tag so we can flush all competition order with levels
     * @param bool $paginate
     * @param bool $is_Auditing indecate if level being audtited so order will changed a lot
     * @return array 
     */
    public function getComptitionUsersOreder($model, $limit, $isCompetition, $compeitionId, $paginate, $is_Auditing = false)
    {
        $cacheTags = $this->getCacheTags('competition_users_order',$compeitionId);
        if($is_Auditing){
            $cacheDuration = $this->getCacheDuration('getComptitionUsersOrederAuditing');
        }else{
            $cacheDuration = $this->getCacheDuration('getComptitionUsersOreder');
        }
        $cacheKey = $this->buildCacheKey('getComptitionUsersOreder',
                        [
                            'model_id' => $model->id,
                            'limit' => $limit,
                            'is_competition' => $isCompetition,
                            'paginate' => $paginate
                        ]
                    );
        return Cache::tags($cacheTags)
            ->remember(
            $cacheKey,
            $cacheDuration,
            function () use ($model, $limit, $isCompetition, $paginate) {
                return CompetitionsOrder::getCompetitorsOrder(
                    $model,
                    limit: $limit,
                    isCompetition: $isCompetition,
                    paginate: $paginate
                );
            }
        );
    }

    /**
     * get comptition list for guest users
     */
    public function getUsersCompetitions($user, $filters = [], $page = 1, $perPage = 10)
    {
        $cacheKey = $this->buildCacheKey('getUsersComptitions', [
            'userId' => $user?->id,
            'filters' => $filters,
            'perPage' => $perPage,  
            'page' => $page
        ]);
        
        $cacheTags = $this->getCacheTags('competitions');
        $cacheDuration = $this->getCacheDuration('getUsersComptitions');

        return Cache::tags($cacheTags)
            ->remember($cacheKey, $cacheDuration, function () use ($user, $filters, $page, $perPage) {
                if($user){
                    return $this->userCompetitionRepository
                    ->getUserCompetitions($user,$filters, $page, $perPage);
                }
                
                return $this->userCompetitionRepository
                ->getAllPublicCompetitions($filters, $page, $perPage);
            });
    }

    public function getCompetitionDetail($compeitionSlug)
    {
        $cacheTags = $this->getCacheTags('competitions',$compeitionSlug);
        
        $cacheDuration = $this->getCacheDuration('getCompetitionDetail');
        $cacheKey = $this->buildCacheKey('getCompetitionDetail',[
            'slug' => $compeitionSlug
        ]);

        return Cache::tags($cacheTags)
            ->remember(
            $cacheKey,
            $cacheDuration,
            function () use ($compeitionSlug) {
                return Competition::with('levels')->where('slug' , $compeitionSlug)->firstOrFail();
            }
        );
    }

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
     * Invalidate users audting info 
     */
    public function invalidateUsersAuditingInfo($adminId): void
    {
        $cacheTags = $this->getCacheTags('users_responses_auditing_system', $adminId);
        Cache::tags($cacheTags)->flush();
    }

    /**
     * Invalidate comptition users order
     */
    public function invalidateComptitionUsersOreder($compeitionId): void
    {
        $cacheTags = $this->getCacheTags('competition_users_order', $compeitionId);
        Cache::tags($cacheTags)->flush();
    }

    /**
     * Invalidate comptition users order
     */
    public function invalidateComptitionDetail($compeitionSlug): void
    {
        Cache::key($this->buildCacheKey('getCompetitionDetail',[
            'slug' => $compeitionSlug
        ]))->flush();
    }

    /**
     * Invalidate users comptitions informations
     */
    public function invalidateUsersComptitionInfo(): void
    {
        Cache::tags('competitions')->flush();
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
            'getComptitionUsersOreder' => 3600, // 1 hours
            'getComptitionUsersOrederAuditing' => 300, // 5 minut
            'getCompetitionDetail' => 3600, // 1 hours
            'getUsersComptitions' => 3600 // 1hour

        ];

        return $cacheDurations[$method] ?? 600; // Default 10 minutes
    }
}