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

class RunningCompetitionSeeder extends Seeder
{
    public function run(): void
    {
        // Remove only our running test data
        Competition::where('title', 'Running Test Competition')->delete();
        Admin::whereIn('email', [
            'test.running_competition.admin@example.com',
            'test.running_competition.auditor1@example.com',
            'test.running_competition.auditor2@example.com'
        ])->forceDelete();
        User::where('email', 'test.running_competition.user@example.com')->forceDelete();

        // Create admin users
        $admin = Admin::firstOrCreate(
            ['email' => 'test.running_competition.admin@example.com'],
            [
                'name' => 'Test Running Admin',
                'password' => Hash::make('password'),
                'birthdate' => '1990-01-01',
                'gender' => 'male',
            ]
        );

        // Create running competition
        $competition = Competition::firstOrCreate(
            ['title' => 'Running Test Competition'],
            [
                'description' => 'A test competition that is currently running',
                'admin_id' => $admin->id,
                'start_date' => Carbon::now()->subHours(2), // Started 2 hours ago
                'age_start' => 18,
                'age_end' => 30,
                'levels_number' => 1,
                'status' => 'active', // Competition is active
            ]
        );

        // Create running level
        $level = Level::firstOrCreate(
            [
                'competition_id' => $competition->id,
                'name' => "Running Test Level"
            ],
            [
                'description' => "Test Description for Running Level",
                'admin_id' => $admin->id,
                'questions_number' => 5,
                'start_date' => Carbon::now()->subMinutes(30), // Started 30 minutes ago
                'duration' => 120, // 2 hours duration
                'status' => '1', // Level is active
            ]
        );

        // Create 5 questions for the running level
        for ($i = 1; $i <= 5; $i++) {
            Question::firstOrCreate(
                [
                    'level_id' => $level->id,
                    'question_text' => "Running Test Question $i"
                ],
                [
                    'max_score' => 10,
                    'duration' => 15, // 15 minutes per question
                ]
            );
        }

        // Create test user for running competition
        $user = User::firstOrCreate(
            ['email' => 'test.running_competition.user@example.com'],
            [
                'name' => "Test Running User",
                'password' => Hash::make('password'),
                'birthdate' => '2000-01-01',
                'gender' => 'male',
                'email_verified_at' => now(),
                'guest' => false,
            ]
        );

        // Add user to competition
        $competition->users()->syncWithoutDetaching([$user->id]);

        // Add auditors to competition
        $competition->auditors()->syncWithoutDetaching([$admin->id]);

    }
} 