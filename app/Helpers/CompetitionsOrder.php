<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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

    private static function fetchUserScores(Model $model, bool $isCompetition, bool $limit, bool $paginate): Collection|LengthAwarePaginator
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
            : ($paginate ? $query->paginate(10) : $query->get());
    }

    private static function checkAuditStatus(Model $model, bool $isCompetition): bool
    {
        if ($isCompetition) {
            $unfinished = $model->levels()
                ->whereIn('status', ['0', '1'])->exists();

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
}
