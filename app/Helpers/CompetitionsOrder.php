<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Models\Competition\Level;

class CompetitionsOrder
{
    /**
     * Get the competitors order for a given model (competition or level)
     *
     * @param Model $model The model to get the competitors order for
     * @param bool $limit Whether to limit the number of results
     * @param bool $isCompetition Whether the model is a competition
     * @param bool $paginate Whether to paginate the results
     * @return array users:collection of users and audit_finish:bool if the audit is finished
     */
    public static function getCompetitorsOrder(Model $model, bool $limit, bool $isCompetition, bool $paginate = true): array
    {
        $users = self::fetchUserScores($model, $isCompetition, $limit, $paginate);
        $auditFinished = self::checkAuditStatus($model, $isCompetition);

        return [
            'users' => $users,
            'audit_finish' => $auditFinished,
        ];
    }

    /**
     * Fetch user scores for a given model (competition or level)
     *
     * @param Model $model The model to fetch user scores for
     * @param bool $isCompetition Whether the model is a competition
     * @param bool $limit Whether to limit the number of results
     * @param bool $paginate Whether to paginate the results
     * @return Collection|LengthAwarePaginator
     */
    private static function fetchUserScores(Model $model, bool $isCompetition, bool $limit, bool $paginate)
    {
        $query = User::withSum(['responses as total_score' => function ($q) use ($model, $isCompetition) {
            $q->whereHas('question', function ($q2) use ($model, $isCompetition) {
                if ($isCompetition) {
                    $q2->whereHas('level', fn ($q3) => $q3->where('competition_id', $model->id));
                } else {
                    $q2->where('level_id', $model->id);
                }
            });
        }], 'score')
        ->whereHas('responses', function ($q) use ($model, $isCompetition) {
            $q->whereHas('question', function ($q2) use ($model, $isCompetition) {
                if ($isCompetition) {
                    $q2->whereHas('level', fn ($q3) => $q3->where('competition_id', $model->id));
                } else {
                    $q2->where('level_id', $model->id);
                }
            });
        })
        ->orderByDesc('total_score');

        return $limit
            ? $query->limit(3)->get()
            : ($paginate ? $query->paginate(PaginationHelper::perPage(set_default:10)) : $query->get());
    }

    /**
     * Check if the audit is finished for a given model (competition or level)
     *
     * @param Model $model The model to check the audit status for
     * @param bool $isCompetition Whether the model is a competition
     * @return bool
     */
    private static function checkAuditStatus(Model $model, bool $isCompetition): bool
    {
        if ($isCompetition) {
            $unfinished = $model->levels()
                ->whereIn('status', [Level::STATUS_PENDING, Level::STATUS_ACTIVE])->exists();

            $unaudited = $model->levels->sum(function ($level) {
                return $level->questions()
                    ->withCount(['responses as all_audited' => fn ($q) => $q->whereNull('admin_id')])
                    ->get()
                    ->sum('all_audited');
            });

            return !$unfinished && $unaudited === 0;
        }

        $unaudited = $model->questions()
            ->withCount(['responses as all_audited' => fn ($q) => $q->whereNull('admin_id')])
            ->get()
            ->sum('all_audited');

        return $unaudited === 0;
    }

    /**
     * Get the competitors order for a batch of competitions
     *
     * @param Collection $competitions The competitions to get the competitors order for
     * @param bool $limit Whether to limit the number of results
     * @return array
     */
    public static function getBatchCompetitorsOrder($competitions, bool $limit = false): array
    {
        if (empty($competitions)) {
            return [];
        }
        $competitionIds = $competitions->pluck('id')->toArray();
        if(!empty($competitionIds)){
            $results = DB::select("
                        SELECT 
                            u.id,
                            u.name,
                            l.competition_id,
                            SUM(r.score) as total_score
                        FROM users u
                        INNER JOIN responses r ON u.id = r.user_id
                        INNER JOIN questions q ON r.question_id = q.id  
                        INNER JOIN levels l ON q.level_id = l.id
                        WHERE l.competition_id IN (" . implode(',', array_fill(0, count($competitionIds), '?')) . ")
                        AND u.deleted_at IS NULL 
                        AND u.guest = 0
                        GROUP BY u.id, u.name, l.competition_id
                        ORDER BY l.competition_id, SUM(r.score) DESC
                    ", $competitionIds);
        }else{
            $results = collect();
        }
       
        
        // Get audit status (simplified for batch)
        $auditFinished = true; // You can optimize this separately if needed
        
        // Group and format results
        $competitorsByCompetition = [];
        foreach ($results as $result) {
            if (!isset($competitorsByCompetition[$result->competition_id])) {
                $competitorsByCompetition[$result->competition_id] = collect();
            }
            
            $competitorsByCompetition[$result->competition_id]->push((object)[
                'id' => $result->id,
                'name' => $result->name,
                'total_score' => $result->total_score,
            ]);
            
            if ($limit && $competitorsByCompetition[$result->competition_id]->count() >= 3) {
                continue;
            }
        }
        
        // Format return structure to match original method
        $finalResults = [];
        foreach ($competitionIds as $competitionId) {
            $finalResults[$competitionId] = [
                'users' => $competitorsByCompetition[$competitionId] ?? collect(),
                'audit_finish' => $auditFinished,
            ];
        }
        
        return $finalResults;
    }   
}
