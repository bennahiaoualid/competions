<?php

namespace App\Http\Helpers;

use App\Models\User;

class UsersGlobalOrder
{
    public static function getUsersGlobalOrder(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {

        // Fetch the results


        return User::query()
            ->select('users.id','users.name')
            ->selectRaw('SUM(global_responses.score) as total_score')  // Calculate total score
            ->join('global_responses', 'users.id', '=', 'global_responses.user_id')
            ->groupBy('users.id','users.name')  // Group by user ID
        ->orderByDesc('total_score')  // Order by total score, descending
        ->paginate(10);
    }
}
