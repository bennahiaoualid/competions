<?php

namespace App\Http\Helpers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UsersGlobalOrder
{
    public static function getUsersGlobalOrder(): array
    {

        // Fetch the results


       /* return User::query()
            ->select('users.id','users.name')
            ->selectRaw('SUM(global_responses.score) as total_score')  // Calculate total score
            ->join('global_responses', 'users.id', '=', 'global_responses.user_id')
            ->groupBy('users.id','users.name')  // Group by user ID
        ->orderByDesc('total_score')  // Order by total score, descending
        ->paginate(10);*/
        $userRank = null;
        $userPage = null;
        if (auth()->check() && Auth::user() instanceof User) {
            $userId = Auth::id();
            $rankQuery = User::query()
                ->select('users.id')
                ->selectRaw('COALESCE(SUM(global_responses.score), 0) as total_score')
                ->selectRaw('COALESCE(SUM(global_responses.response_duration), 0) as total_time')
                ->leftJoin('global_responses', 'users.id', '=', 'global_responses.user_id')
                ->groupBy('users.id')
                ->orderByDesc('total_score')
                ->orderBy('total_time')
                ->orderBy('users.name');

            $rankedUsers = $rankQuery->get();

            foreach ($rankedUsers as $index => $user) {
                if ($user->id == $userId) {
                    $userRank = $index + 1;
                    break;
                }
            }

            if ($userRank) {
                $userPage = (int) floor(($userRank - 1) / 10) + 1;
            }
        }
        $users =  User::query()
            ->select('users.id', 'users.name')
            ->selectRaw('COALESCE(SUM(global_responses.score), 0) as total_score') // تجنب القيم NULL
            ->selectRaw('COALESCE(SUM(global_responses.response_duration), 0) as total_time') // حساب مجموع مدة الإجابة
            ->leftJoin('global_responses', 'users.id', '=', 'global_responses.user_id') // السماح بجلب كل المستخدمين
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_score') // ترتيب حسب المجموع
            ->orderBy('total_time')
            ->orderBy('users.name') // ترتيب إضافي
            ->paginate(10);
        return ["users" => $users, "userRank" => $userRank, "userPage" => $userPage];
    }
}
