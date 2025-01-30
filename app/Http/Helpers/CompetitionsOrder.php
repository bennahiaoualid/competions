<?php

namespace App\Http\Helpers;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;


class CompetitionsOrder
{
    /**
     * @param Model $model level or competition
     * @param bool $limit
     * @param bool $is_competition
     * @return array
     */
    public static function getCompetitorsOrder(Model $model, bool $limit, bool $is_competition , bool $paginate = true): array
    {

        $users = User::query()
            ->select('users.id','users.name')
            ->selectRaw('SUM(responses.score) as total_score')  // Calculate total score
            ->join('responses', 'users.id', '=', 'responses.user_id')
            ->join('questions', 'responses.question_id', '=', 'questions.id');
        if($is_competition){
            $users->join('levels', 'questions.level_id', '=', 'levels.id')
                ->where('levels.competition_id', $model->id);
        }else{
            $users->where('questions.level_id', $model->id);
        }
        $users->groupBy('users.id','users.name')  // Group by user ID
        ->orderByDesc('total_score');  // Order by total score, descending
        if($limit){
            $users->limit(3);
            $users = $users->get();// Fetch the results
        }else{
            if ($paginate){
                $users = $users->paginate(10);  // Fetch the results
            }
            else{
                $users = $users->get();
            }

        };


        // check if the level responses all audited
        if($is_competition){
            $audit = 0;

            $is_levels_fnished = $model->levels()
                    ->where('status',"0")
                    ->orWhere('status',"1")
                    ->count() == 0;

            foreach ($model->levels as $level){
                $audit += $level->questions()->withCount(['responses as all_audited' => function ($query) {
                    $query->whereNull('admin_id');}])
                    ->get()
                    ->sum('all_audited');
            }
            $audit_finish = $audit == 0 && $is_levels_fnished;
        }else{
            $audit_finish = ($model->questions()->withCount(['responses as all_audited' => function ($query) {
                    $query->whereNull('admin_id');}])
                    ->get()
                    ->sum('all_audited')) == 0;
        }

        return ['users' => $users, 'audit_finish' => $audit_finish];
    }
}

