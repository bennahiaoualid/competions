<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\GuestUsers\UserLeaderboardResult;
use Illuminate\Support\Facades\DB;
use App\Services\CashManagment\GuestUserCacheService;

class UpdateUserLeaderboardResults extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-user-leaderboard-results';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating user leaderboard results...');

        User::WithoutGlobalScope('nonGuest')->chunk(500, function (
            $users
        ) {
            $data = [];
            foreach ($users as $user) {
                $totals = DB::table('global_responses')
                    ->where('user_id', $user->id)
                    ->selectRaw('COALESCE(SUM(score), 0) as total_score, COALESCE(SUM(response_duration), 0) as total_duration')
                    ->first();

                $data[] = [
                    'user_id' => $user->id,
                    'total_score' => $totals->total_score,
                    'total_duration' => $totals->total_duration,
                    'updated_at' => now(),
                    'created_at' => now(),
                ];
            }
            // Upsert in bulk
            UserLeaderboardResult::upsert(
                $data,
                ['user_id'], // unique by user_id
                ['total_score', 'total_duration', 'updated_at']
            );
        });

        $this->info('Leaderboard results updated.');
    }
}
