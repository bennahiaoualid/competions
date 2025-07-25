<?php

namespace App\Helpers;

use App\Helpers\PaginationHelper;
use Illuminate\Support\Facades\Auth;
use App\Models\GuestUsers\UserLeaderboardResult;

class UsersGlobalOrder
{
    public static function getUsersGlobalOrder(): array
    {
        $userRank = null;
        $userPage = null;
        $perPage = PaginationHelper::perPage(10);

        // Leaderboard: join with users for names
        $users = UserLeaderboardResult::query()
            ->join('users', 'user_leaderboard_results.user_id', '=', 'users.id')
            ->select('users.id', 'users.name', 'user_leaderboard_results.total_score', 'user_leaderboard_results.total_duration')
            ->orderByDesc('user_leaderboard_results.total_score')
            ->orderBy('user_leaderboard_results.total_duration')
            ->orderBy('users.name')
            ->paginate($perPage);

        // Find current user's rank and page
        if (Auth::check()) {
            $userId = Auth::id();
            $allUserIds = UserLeaderboardResult::query()
                ->orderByDesc('total_score')
                ->orderBy('total_duration')
                ->orderBy('user_id')
                ->pluck('user_id')
                ->toArray();
            $userRank = array_search($userId, $allUserIds);
            if ($userRank !== false) {
                $userRank += 1; // array_search is zero-based
                $userPage = (int) floor(($userRank - 1) / $perPage) + 1;
            }
        }

        return ["users" => $users, "user_rank" => $userRank, "user_page" => $userPage];
    }
}
