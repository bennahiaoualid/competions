<?php

namespace Database\Seeders;

use App\Models\Admin\Admin;
use App\Models\Competition\Competition;
use App\Models\Competition\Level;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CompetitionBulkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $this->cleanPreviouslySeededCompetitions();

            $admin = Admin::query()->first();
            if (!$admin) {
                $admin = Admin::factory()->create();
            }

            $statuses = ['pending', 'active', 'finished'];

            for ($i = 1; $i <= 2000; $i++) {
                $title = 'comptition_seeder_' . $i; // keep exact requested prefix

                $status = $statuses[($i - 1) % count($statuses)];

                $startDate = now()->addDays($i % 60)->startOfMinute();

                $competition = Competition::query()->create([
                    'title' => $title,
                    'description' => null,
                    'admin_id' => $admin->id,
                    'start_date' => $startDate,
                    'age_start' => 10,
                    'age_end' => 25,
                    'levels_number' => 2,
                    'status' => $status,
                    'participants_sync_status' => 'completed',
                    'last_synced_at' => now(),
                    'is_suspended' => false,
                    'winner_gifts' => 0,
                    'multi_winner' => false,
                    'ai_auditing' => false,
                    'auditing_time_for_level' => 30,
                ]);

                // Create exactly 2 levels for each competition
                for ($levelIndex = 1; $levelIndex <= 2; $levelIndex++) {
                    Level::query()->create([
                        'name' => 'Level ' . $levelIndex,
                        'description' => null,
                        'competition_id' => $competition->id,
                        'admin_id' => $admin->id,
                        'questions_number' => 10,
                        'start_date' => $startDate->copy()->addDays($levelIndex),
                        'duration' => 60,
                        'status' => 'pending',
                    ]);
                }
            }
        });
    }

    /**
     * Remove previously inserted competitions by this seeder (and related levels via cascade).
     */
    protected function cleanPreviouslySeededCompetitions(): void
    {
        // Levels are set to cascade on delete, so deleting competitions removes their levels.
        Competition::query()
            ->where('title', 'like', 'comptition_seeder_%')
            ->delete();
    }
} 