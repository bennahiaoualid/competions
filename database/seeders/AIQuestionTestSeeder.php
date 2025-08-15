<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Admin\Admin;
use App\Models\GuestUsers\GlobalQuestion;
use App\Models\GuestUsers\Choice;
use App\Models\Payment\CoinBalance;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AIQuestionTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->cleanup();
        $this->createTestData();
    }

    /**
     * Clean up existing test data
     */
    private function cleanup(): void
    {
        // Delete test users
        User::where('email', 'like', '%aiquestion.test%')->forceDelete();
        
        // Delete test admins
        Admin::where('email', 'like', '%aiquestion.test%')->forceDelete();
        
        // Delete test questions
        GlobalQuestion::where('question_text', 'like', '%TEST%')->delete();
        
        // Delete test coin balances
        
    }

    /**
     * Create test data
     */
    private function createTestData(): void
    {
        // Create test admin for approval
        $admin = Admin::factory()->create([
            'name' => 'Test Admin',
            'email' => 'testadmin@aiquestion.test',
            'password' => Hash::make('12345678'),
        ]);

        // Create main test user
        $mainUser = User::factory()->create([
            'name' => 'Main Test User',
            'email' => 'mainuser@aiquestion.test',
            'password' => Hash::make('12345678'),
            'email_verified_at' => now(),
        ]);

        // Create second test user
        $secondUser = User::factory()->create([
            'name' => 'Second Test User',
            'email' => 'seconduser@aiquestion.test',
            'password' => Hash::make('12345678'),
            'email_verified_at' => now(),
        ]);

        // Create coin balances for users
        /*CoinBalance::create([
            'user_id' => $mainUser->id,
            'balance' => 100,
        ]);

        CoinBalance::create([
            'user_id' => $secondUser->id,
            'balance' => 100,
        ]);*/

        // Create 2 normal questions
        $this->createNormalQuestions($admin);

        // Create 4 AI questions with different states
        $this->createAIQuestions($admin, $mainUser, $secondUser);

        $this->command->info('AI Question Test Data seeded successfully!');
        $this->command->info('Main User: mainuser@test.com (password: password)');
        $this->command->info('Second User: seconduser@test.com (password: password)');
        $this->command->info('Admin: testadmin@test.com (password: password)');
    }

    /**
     * Create normal questions
     */
    private function createNormalQuestions(Admin $admin): void
    {
        $questions = [
            [
                'question_text' => 'TEST: What is 2 + 2?',
                'score' => 10,
                'duration' => 60,
                'text_direction' => 'ltr',
                'admin_id' => $admin->id,
                'approved' => $admin->id,
                'ai' => false,
                'user_id' => null,
            ],
            [
                'question_text' => 'TEST: What is the capital of France?',
                'score' => 10,
                'duration' => 60,
                'text_direction' => 'ltr',
                'admin_id' => $admin->id,
                'approved' => $admin->id,
                'ai' => false,
                'user_id' => null,
            ]
        ];

        foreach ($questions as $questionData) {
            $question = GlobalQuestion::create($questionData);
            $this->createChoicesForQuestion($question);
        }
    }

    /**
     * Create AI questions with different states
     */
    private function createAIQuestions(Admin $admin, User $mainUser, User $secondUser): void
    {
        $now = Carbon::now();

        $aiQuestions = [
            // Main user - AI question less than 48h (exclusive)
            [
                'question_text' => 'TEST AI: What is the chemical formula for water?',
                'score' => 10,
                'duration' => 60,
                'text_direction' => 'ltr',
                'admin_id' => null,
                'approved' => null,
                'ai' => true,
                'user_id' => $mainUser->id,
                'created_at' => $now->copy()->subHours(12), // 12 hours ago
            ],
            // Second user - AI question less than 48h (exclusive)
            [
                'question_text' => 'TEST AI: What is the largest planet in our solar system?',
                'score' => 10,
                'duration' => 60,
                'text_direction' => 'ltr',
                'admin_id' => null,
                'approved' => $admin->id,
                'ai' => true,
                'user_id' => $secondUser->id,
                'created_at' => $now->copy()->subHours(24), // 24 hours ago
            ],
            // Premium question after 48h - approved
            [
                'question_text' => 'TEST AI: What is the speed of light in vacuum?',
                'score' => 10,
                'duration' => 60,
                'text_direction' => 'ltr',
                'admin_id' => null,
                'approved' => $admin->id,
                'ai' => true,
                'user_id' => $secondUser->id,
                'created_at' => $now->copy()->subHours(72), // 72 hours ago (3 days)
            ],
            // Premium question after 48h - not approved
            [
                'question_text' => 'TEST AI: What is the atomic number of carbon?',
                'score' => 10,
                'duration' => 60,
                'text_direction' => 'ltr',
                'admin_id' => null,
                'approved' => null, // Not approved
                'ai' => true,
                'user_id' => $secondUser->id,
                'created_at' => $now->copy()->subHours(96), // 96 hours ago (4 days)
            ]
        ];

        foreach ($aiQuestions as $questionData) {
            $question = GlobalQuestion::create($questionData);
            $this->createChoicesForQuestion($question);
        }
    }

    /**
     * Create choices for a question
     */
    private function createChoicesForQuestion(GlobalQuestion $question): void
    {
        $choices = [
            [
                'choice_text' => 'Choice A',
                'correct' => true,
            ],
            [
                'choice_text' => 'Choice B',
                'correct' => false,
            ],
            [
                'choice_text' => 'Choice C',
                'correct' => false,
            ],
            [
                'choice_text' => 'Choice D',
                'correct' => false,
            ]
        ];

        foreach ($choices as $choiceData) {
            Choice::create([
                'question_id' => $question->id,
                'choice_text' => $choiceData['choice_text'],
                'correct' => $choiceData['correct'],
            ]);
        }
    }
}
