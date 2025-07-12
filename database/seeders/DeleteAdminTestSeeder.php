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

class DeleteAdminTestSeeder extends Seeder
{
    public function run(): void
    {
        // Clean up all test data first
        $this->cleanupTestData();

        // Create target admin that will be deleted
        $targetAdmin = Admin::firstOrCreate(
            ['email' => 'target.auditor@test.com'],
            [
                'name' => 'Target Auditor To Delete',
                'password' => Hash::make('password'),
                'birthdate' => '1990-01-01',
                'gender' => 'male',
            ]
        );

        // Create additional admins for testing
        $admin1 = Admin::firstOrCreate(
            ['email' => 'admin1@test.com'],
            [
                'name' => 'Test Admin One',
                'password' => Hash::make('password'),
                'birthdate' => '1985-01-01',
                'gender' => 'male',
            ]
        );

        $admin2 = Admin::firstOrCreate(
            ['email' => 'admin2@test.com'],
            [
                'name' => 'Test Admin Two',
                'password' => Hash::make('password'),
                'birthdate' => '1988-01-01',
                'gender' => 'female',
            ]
        );

        // Create users with admin_id pointing to target admin
        $user1 = User::firstOrCreate(
            ['email' => 'user1.target.admin@test.com'],
            [
                'name' => 'User One - Target Admin',
                'password' => Hash::make('password'),
                'birthdate' => '2000-01-01',
                'gender' => 'male',
                'email_verified_at' => now(),
                'guest' => false,
                'admin_id' => $targetAdmin->id,
            ]
        );

        $user2 = User::firstOrCreate(
            ['email' => 'user2.target.admin@test.com'],
            [
                'name' => 'User Two - Target Admin',
                'password' => Hash::make('password'),
                'birthdate' => '2001-01-01',
                'gender' => 'female',
                'email_verified_at' => now(),
                'guest' => false,
                'admin_id' => $targetAdmin->id,
            ]
        );

        // Create competitions with different statuses
        $competitions = $this->createCompetitions($admin1);

        // Make target admin auditor in pending and finished competitions
        $this->assignTargetAdminAsAuditor($targetAdmin, $competitions);

        // Create levels and questions for each competition
        $this->createLevelsAndQuestions($competitions, $admin1);

        // Add users to competitions
        $this->addUsersToCompetitions($competitions, [$user1, $user2]);

        // Create responses and assign auditors for finished competition
        $this->createResponsesAndAssignAuditors($targetAdmin, $competitions);

        $this->command->info('SafeDeleteAuditorTestSeeder completed successfully!');
        $this->command->info("Target admin ID: {$targetAdmin->id}");
        $this->command->info("Target admin email: {$targetAdmin->email}");
    }

    private function cleanupTestData(): void
    {
        // Delete test competitions
        Competition::whereIn('title', [
            'Test Competition - Pending',
            'Test Competition - Active',
            'Test Competition - Finished'
        ])->delete();

        // Delete test users
        User::whereIn('email', [
            'user1.target.admin@test.com',
            'user2.target.admin@test.com'
        ])->forceDelete();

        // Delete test admins
        Admin::whereIn('email', [
            'target.auditor@test.com',
            'admin1@test.com',
            'admin2@test.com'
        ])->forceDelete();

        // Clean up related data
        DB::table('admin_competition')->whereIn('admin_id', function($query) {
            $query->select('id')->from('admins')->whereIn('email', [
                'target.auditor@test.com',
                'admin1@test.com',
                'admin2@test.com'
            ]);
        })->delete();

        DB::table('level_admin_user')->whereIn('admin_id', function($query) {
            $query->select('id')->from('admins')->whereIn('email', [
                'target.auditor@test.com',
                'admin1@test.com',
                'admin2@test.com'
            ]);
        })->delete();
    }

    private function createCompetitions(Admin $admin1): array
    {
        $competitions = [];

        // Pending competition
        $competitions['pending'] = Competition::create([
            'title' => 'Test Competition - Pending',
            'description' => 'A test competition with pending status for SafeDeleteAuditor testing',
            'admin_id' => $admin1->id,
            'start_date' => Carbon::now()->addDays(7),
            'age_start' => 18,
            'age_end' => 30,
            'levels_number' => 2,
            'status' => Competition::STATUS_PENDING,
        ]);

        // Active competition
        $competitions['active'] = Competition::create([
            'title' => 'Test Competition - Active',
            'description' => 'A test competition with active status for SafeDeleteAuditor testing',
            'admin_id' => $admin1->id,
            'start_date' => Carbon::now()->subDays(1),
            'age_start' => 18,
            'age_end' => 30,
            'levels_number' => 2,
            'status' => Competition::STATUS_ACTIVE,
        ]);

        // Finished competition
        $competitions['finished'] = Competition::create([
            'title' => 'Test Competition - Finished',
            'description' => 'A test competition with finished status for SafeDeleteAuditor testing',
            'admin_id' => $admin1->id,
            'start_date' => Carbon::now()->subDays(10),
            'age_start' => 18,
            'age_end' => 30,
            'levels_number' => 2,
            'status' => Competition::STATUS_COMPLETED,
        ]);

        return $competitions;
    }

    private function assignTargetAdminAsAuditor(Admin $targetAdmin, array $competitions): void
    {
        // Make target admin auditor in pending and finished competitions only
        $competitions['pending']->auditors()->attach($targetAdmin->id);
        $competitions['finished']->auditors()->attach($targetAdmin->id);
        
        // Note: Not adding to active competition to test the logic
    }

    private function createLevelsAndQuestions(array $competitions, Admin $admin1): void
    {
        foreach ($competitions as $status => $competition) {
            // Create 2 levels for each competition
            for ($levelIndex = 1; $levelIndex <= 2; $levelIndex++) {
                $level = Level::create([
                    'name' => "Level {$levelIndex} - {$status}",
                    'description' => "Test level {$levelIndex} for {$status} competition",
                    'competition_id' => $competition->id,
                    'admin_id' => $admin1->id,
                    'questions_number' => 3,
                    'start_date' => $this->getLevelStartDate($status, $levelIndex),
                    'duration' => 120,
                    'status' => $this->getLevelStatus($status),
                ]);

                // Create 3 questions for each level
                for ($questionIndex = 1; $questionIndex <= 3; $questionIndex++) {
                    Question::create([
                        'question_text' => "Question {$questionIndex} for Level {$levelIndex} - {$status}",
                        'max_score' => 10,
                        'duration' => rand(120, 300),
                        'level_id' => $level->id,
                    ]);
                }
            }
        }
    }

    private function getLevelStartDate(string $status, int $levelIndex): Carbon
    {
        return match($status) {
            'pending' => Carbon::now()->addDays(7 + $levelIndex),
            'active' => Carbon::now()->subDays(1 + $levelIndex),
            'finished' => Carbon::now()->subDays(10 + $levelIndex),
            default => Carbon::now(),
        };
    }

    private function getLevelStatus(string $status): string
    {
        return match($status) {
            'pending' => Level::STATUS_PENDING,
            'active' => Level::STATUS_ACTIVE,
            'finished' => Level::STATUS_FINISHED,
            default => Level::STATUS_PENDING,
        };
    }

    private function addUsersToCompetitions(array $competitions, array $users): void
    {
        foreach ($competitions as $competition) {
            $competition->users()->attach(array_map(fn($user) => $user->id, $users));
        }
    }

    private function createResponsesAndAssignAuditors(Admin $targetAdmin, array $competitions): void
    {
        // Only create responses for finished competition
        $finishedCompetition = $competitions['finished'];
        
        foreach ($finishedCompetition->levels as $level) {
            $questions = $level->questions;
            $users = $finishedCompetition->users;

            foreach ($users as $user) {
                foreach ($questions as $question) {
                    // Create response
                    $response = Response::create([
                        'response_text' => "Response from {$user->name} for question {$question->id}",
                        'question_id' => $question->id,
                        'user_id' => $user->id,
                        'response_duration' => rand(60, 360),
                        'score' => 0, // Will be scored by target admin
                    ]);

                    // Assign target admin to audit this user in this level
                    DB::table('level_admin_user')->insertOrIgnore([
                        'level_id' => $level->id,
                        'user_id' => $user->id,
                        'admin_id' => $targetAdmin->id,
                    ]);
                }
            }

            // Make target admin audit some responses in the finished competition
            $responsesToAudit = Response::whereHas('question', function($query) use ($level) {
                $query->where('level_id', $level->id);
            })->take(3)->get();

            foreach ($responsesToAudit as $response) {
                $response->update([
                    'admin_id' => $targetAdmin->id,
                    'score' => rand(5, 10),
                    'final_score' => rand(4, 9),
                ]);
            }
        }
    }
} 