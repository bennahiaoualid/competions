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
use Illuminate\Support\Collection;

class CompetitionLifecycleSeeder extends Seeder
{
    public function run(): void
    {
        // Remove only our test data
        Competition::where('title', 'Test Completed Competition')->delete();
        Admin::whereIn('email', [
            'test.admin@example.com',
            'test.auditor1@example.com',
            'test.auditor2@example.com'
        ])->delete();
        User::whereIn('email', [
            'test.user1@example.com',
            'test.user2@example.com',
            'test.user3@example.com',
            'test.user4@example.com',
            'test.user5@example.com'
        ])->delete();

        // Create admin users
        $admin = Admin::where('email', 'oualidbennahia@gmail.com')->first();

        $auditor1 = Admin::firstOrCreate(
            ['email' => 'test.auditor1@example.com'],
            [
                'name' => 'Test Auditor One',
                'password' => Hash::make('password'),
                'birthdate' => '1991-01-01',
                'gender' => 'female',
            ]
        );

        $auditor2 = Admin::firstOrCreate(
            ['email' => 'test.auditor2@example.com'],
            [
                'name' => 'Test Auditor Two',
                'password' => Hash::make('password'),
                'birthdate' => '1992-01-01',
                'gender' => 'male',
            ]
        );

        // Create competition with past dates
        $competition = Competition::firstOrCreate(
            ['title' => 'Test Completed Competition'],
            [
                'description' => 'A test competition that has completed its lifecycle',
                'admin_id' => $admin->id,
                'start_date' => Carbon::now()->subDays(30), // Started 30 days ago
                'age_start' => 18,
                'age_end' => 30,
                'levels_number' => 3,
                'status' => '3', // Competition is finished
            ]
        );

        // Create levels with past dates
        $levels = [];
        $startDate = Carbon::now()->subDays(30); // Start from 30 days ago
        for ($i = 1; $i <= 3; $i++) {
            $levels[] = Level::firstOrCreate(
                [
                    'competition_id' => $competition->id,
                    'name' => "Test Level $i"
                ],
                [
                    'description' => "Test Description for Level $i",
                    'admin_id' => $admin->id,
                    'questions_number' => 5,
                    'start_date' => $startDate->copy()->addDays($i * 5), // Each level starts 5 days after previous
                    'duration' => 60, // 60 minutes
                    'status' => '3', // All levels are finished
                ]
            );
        }

        // Create questions for each level
        foreach ($levels as $level) {
            $questionsNumber = rand(5, 12);
            for ($i = 1; $i <= $questionsNumber; $i++) {
                Question::firstOrCreate(
                    [
                        'level_id' => $level->id,
                        'question_text' => "Test Question $i for {$level->name}"
                    ],
                    [
                        'max_score' => 10,
                        'duration' => 10, // 10 minutes per question
                    ]
                );
            }
        }

        // Create users
        $users = new Collection();
        for ($i = 1; $i <= 5; $i++) {
            $users->push(User::firstOrCreate(
                ['email' => "test.user$i@example.com"],
                [
                    'name' => "Test User $i",
                    'password' => Hash::make('password'),
                    'birthdate' => '2000-01-01',
                    'gender' => $i % 2 == 0 ? 'female' : 'male',
                    'email_verified_at' => now(),
                    'guest' => false,
                ]
            ));
        }

        // Add users to competition (using syncWithoutDetaching to prevent duplicates)
        $competition->users()->syncWithoutDetaching($users->pluck('id'));

        // Add auditors to competition (using syncWithoutDetaching to prevent duplicates)
        $competition->auditors()->syncWithoutDetaching([$auditor1->id, $auditor2->id]);

        // Create responses for all levels with scores
        foreach ($levels as $level) {
            foreach ($level->questions as $question) {
                foreach ($users as $user) {
                    // Create response with score if it doesn't exist
                    Response::firstOrCreate(
                        [
                            'question_id' => $question->id,
                            'user_id' => $user->id,
                            'admin_id' => $auditor1->id,
                        ],
                        [
                            'response_text' => "Test Response from {$user->name} for question {$question->id}",
                            'response_duration' => rand(1, 10),
                            'score' => rand(1, 10), // Random score between 1-10
                        ]
                    );
                }
            }
        }

        // Create some additional audited responses by the second auditor
        foreach ($levels as $level) {
            $firstQuestion = $level->questions->first();
            foreach ($users as $user) {
                Response::firstOrCreate(
                    [
                        'question_id' => $firstQuestion->id,
                        'user_id' => $user->id,
                        'admin_id' => $auditor2->id,
                    ],
                    [
                        'response_text' => "Test Second audit response from {$user->name}",
                        'response_duration' => rand(1, 10),
                        'score' => rand(1, 10),
                    ]
                );
            }
        }
    }
} 