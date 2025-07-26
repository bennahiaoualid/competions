<?php

namespace Database\Seeders;

use App\Models\Admin\Admin;
use App\Models\Competition\Competition;
use App\Models\Competition\Level;
use App\Models\Competition\Question;
use App\Models\Competition\Response;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AuditingCompetitionSeeder extends Seeder
{
    public function run(): void
    {
        // Remove test data
        Competition::where('title', 'Auditing Test Competition')->delete();
        User::whereIn('email', [
            'test.auditing.user1@example.com',
            'test.auditing.user2@example.com'
        ])->forceDelete();

        // Get first admin as auditor
        $auditor = Admin::first();
        if (!$auditor) {
            throw new \Exception('No admin found in database. Please run AdminSeeder first.');
        }

        // Create test users
        $user1 = User::firstOrCreate(
            ['email' => 'test.auditing.user1@example.com'],
            [
                'name' => 'Test Auditing User One',
                'password' => Hash::make('password'),
                'birthdate' => '2000-01-01',
                'gender' => 'male',
                'email_verified_at' => now(),
                'guest' => false,
            ]
        );

        $user2 = User::firstOrCreate(
            ['email' => 'test.auditing.user2@example.com'],
            [
                'name' => 'Test Auditing User Two',
                'password' => Hash::make('password'),
                'birthdate' => '2001-01-01',
                'gender' => 'female',
                'email_verified_at' => now(),
                'guest' => false,
            ]
        );

        // Create competition
        $competition = Competition::firstOrCreate(
            ['title' => 'Auditing Test Competition'],
            [
                'description' => 'A test competition for auditing functionality',
                'admin_id' => $auditor->id,
                'start_date' => Carbon::now()->subDays(2),
                'age_start' => 18,
                'age_end' => 30,
                'levels_number' => 1,
                'status' => 'active',
            ]
        );

        // Create finished level
        $level = Level::firstOrCreate(
            [
                'competition_id' => $competition->id,
                'name' => 'Auditing Test Level'
            ],
            [
                'description' => 'Test level for auditing functionality',
                'admin_id' => $auditor->id,
                'questions_number' => 4,
                'start_date' => Carbon::now()->subDay(),
                'duration' => 120,
                'status' => '2', // Finished status
            ]
        );

        // Create 4 questions
        $questions = [];
        for ($i = 1; $i <= 4; $i++) {
            $questions[] = Question::firstOrCreate(
                [
                    'level_id' => $level->id,
                    'question_text' => "Auditing Test Question $i"
                ],
                [
                    'max_score' => 10,
                    'duration' => rand(120, 300),
                ]
            );
        }

        // Add users to competition
        $competition->users()->syncWithoutDetaching([$user1->id, $user2->id]);

        // Create responses for both users
        foreach ([$user1, $user2] as $user) {
            foreach ($questions as $question) {
                Response::firstOrCreate(
                    [
                        'question_id' => $question->id,
                        'user_id' => $user->id,
                    ],
                    [
                        'response_text' => "Test response from {$user->name} for question {$question->id}",
                        'response_duration' => rand(60, 360),
                        'score' => 0, // Not yet audited
                    ]
                );
            }
        }

        $competition->auditors()->syncWithoutDetaching([$auditor->id]);

        // Assign users to auditor in the level_admin_user table
        foreach ([$user1, $user2] as $user) {
            DB::table('level_admin_user')->insertOrIgnore([
                'level_id' => $level->id,
                'user_id' => $user->id,
                'admin_id' => $auditor->id,
            ]);
        }
    }
} 