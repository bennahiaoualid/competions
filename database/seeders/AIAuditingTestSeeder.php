<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Admin\Admin;
use App\Models\Competition\Competition;
use App\Models\Competition\Level;
use App\Models\Competition\Question;
use App\Models\Competition\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AIAuditingTestSeeder extends Seeder
{
    protected $admin;
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🧹 Cleaning up previous test data...');
        $this->cleanup();

        $this->admin = $this->getAdmin();

        
        $this->command->info('🚀 Starting AI Auditing Test Seeder...');
        
        // Create test competition
        $competition = $this->createTestCompetition();
        
        // Create test level
        $level = $this->createTestLevel($competition);
        
        // Create test questions
        $questions = $this->createTestQuestions($level);
        
        // Create test users
        $users = $this->createTestUsers();

        // Attach admin to competition
        $this->attachAdminToCompetition($competition);
        
        // Attach users to competition
        $this->attachUsersToCompetition($competition, $users);
        
        // Create test responses (some users with responses, some without)
        $this->createTestResponses($level, $questions, $users);
        
        $this->command->info('✅ AI Auditing Test data created successfully!');
        $this->command->info("📊 Summary:");
        $this->command->info("   - Competition: {$competition->title} (ID: {$competition->id})");
        $this->command->info("   - Level: {$level->name} (ID: {$level->id})");
        $this->command->info("   - Questions: " . $questions->count());
        $this->command->info("   - Users: " . $users->count());
        $this->command->info("   - Users with responses: " . $users->where('has_responses', true)->count());
        $this->command->info("   - Users without responses: " . $users->where('has_responses', false)->count());
        $this->command->info('');
        $this->command->info('🧪 To test AI auditing:');
        $this->command->info('   1. Run: php artisan test tests/Feature/Jobs/Competition/AIAuditingBatchJobTest.php');
        $this->command->info('   2. Or manually dispatch: App\Jobs\Competition\AIAuditingJob::dispatch($level)');
        $this->command->info('');
        $this->command->info('🧹 To clean up test data:');
        $this->command->info('   php artisan db:seed --class=AIAuditingTestSeeder --force');
    }

    /**
     * Clean up all test data created by this seeder
     */
    private function cleanup(): void
    {
        // Clean up in reverse order to avoid foreign key constraints
        //DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        // Clean up responses
        //Response::where('response_text', 'LIKE', '%[AI_TEST]%')->delete();
        
        // Clean up questions
        //Question::where('question_text', 'LIKE', '%[AI_TEST]%')->delete();
        
        // Clean up levels
        //Level::where('name', 'LIKE', '%[AI_TEST]%')->delete();
        
        // Clean up competitions
        Competition::where('title', 'LIKE', '%[AI_TEST]%')->forceDelete();
        
        // Clean up users
        User::where('email', 'LIKE', '%ai-test-%')->forceDelete();
        Admin::where('email', 'LIKE', '%ai-test-%')->forceDelete();

        
        //DB::statement('SET FOREIGN_KEY_CHECKS=1');
        
        $this->command->info('   ✅ Previous test data cleaned up');
    }

    /**
     * get admin
     */
    private function getAdmin(): Admin
    {
        return Admin::where('email', 'oualidbennahia@gmail.com')->first();
    }

    /**
     * Create test competition
     */
    private function createTestCompetition(): Competition
    {
        return Competition::create([
            'title' => 'AI Auditing Test Competition [AI_TEST]',
            'description' => 'This is a test competition for testing AI auditing functionality',
            'start_date' => now()->subDays(7),
            'age_start' => 10,
            'age_end' => 25,
            'levels_number' => 1,
            'participants_sync_status' => 'completed',
            'last_synced_at' => now(),
            'status' => 'active',
            'is_suspended' => false,
            'winner_gifts' => 1000,
            'multi_winner' => false,
            'auditing_time_for_level' => 10,
            'ai_auditing' => true, // Enable AI auditing
            'admin_id' => $this->admin->id, // Assuming admin ID 1 exists
        ]);
    }

    /**
     * Create test level
     */
    private function createTestLevel(Competition $competition): Level
    {
        return Level::create([
            'competition_id' => $competition->id,
            'name' => 'AI Auditing Test Level [AI_TEST]',
            'description' => 'Test level for AI auditing functionality',
            'admin_id' => $this->admin->id, // Assuming admin ID 1 exists
            'start_date' => now()->subDays(1),
            'duration' => 30, // Duration in minutes
            'questions_number' => 5,
            'status' => 'active',
        ]);
    }

    /**
     * Create test questions with perfect responses
     */
    private function createTestQuestions(Level $level): \Illuminate\Support\Collection
    {
        $questions = collect([
            [
                'question_text' => 'What is the capital of France?',
                'perfect_response' => 'Paris is the capital of France.',
                'max_score' => 10,
                'duration' => 60,
                'level_id' => $level->id
            ],
            [
                'question_text' => 'Who wrote Romeo and Juliet?',
                'perfect_response' => 'William Shakespeare wrote Romeo and Juliet.',
                'max_score' => 8,
                'duration' => 45,
                'level_id' => $level->id
            ],
            [
                'question_text' => 'What is the solution of 2x^2 + 4x - 6 = 0?',
                'perfect_response' => 'The solution is x = 1 and x = -3.',
                'max_score' => 5,
                'duration' => 30,
                'level_id' => $level->id
            ],
            [
                'question_text' => 'Explain the concept of photosynthesis.',
                'perfect_response' => 'Photosynthesis is the process by which plants convert sunlight, carbon dioxide, and water into glucose and oxygen.',
                'max_score' => 15,
                'duration' => 90,
                'level_id' => $level->id
            ],
            [
                'question_text' => 'What is the largest planet in our solar system?',
                'perfect_response' => 'Jupiter is the largest planet in our solar system.',
                'max_score' => 6,
                'duration' => 40,
                'level_id' => $level->id
            ]
        ]);

        return $questions->map(function ($questionData) {
            return Question::create($questionData);
        });
    }

    /**
     * Create test users with clear naming convention
     */
    private function createTestUsers(): \Illuminate\Support\Collection
    {
        $users = collect([
            [
                'name' => 'AI Test User 1 - With Responses',
                'email' => 'ai-test-user1-with-responses@example.com',
                'has_responses' => true
            ],
            [
                'name' => 'AI Test User 2 - With Responses',
                'email' => 'ai-test-user2-with-responses@example.com',
                'has_responses' => true
            ],
            [
                'name' => 'AI Test User 3 - With Responses',
                'email' => 'ai-test-user3-with-responses@example.com',
                'has_responses' => true
            ],
            [
                'name' => 'AI Test User 4 - With Responses',
                'email' => 'ai-test-user4-with-responses@example.com',
                'has_responses' => true
            ],
            [
                'name' => 'AI Test User 5 - With Responses',
                'email' => 'ai-test-user5-with-responses@example.com',
                'has_responses' => true
            ],
            [
                'name' => 'AI Test User 6 - No Responses',
                'email' => 'ai-test-user6-no-responses@example.com',
                'has_responses' => true
            ],
            [
                'name' => 'AI Test User 7 - No Responses',
                'email' => 'ai-test-user7-no-responses@example.com',
                'has_responses' => true
            ],
            [
                'name' => 'AI Test User 8 - No Responses',
                'email' => 'ai-test-user8-no-responses@example.com',
                'has_responses' => true
            ]
        ]);

        return $users->map(function ($userData) {
            $user = User::factory()->create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
                'guest' => false,
            ]);
            
            // Add the has_responses flag to the user object for later use
            $user->has_responses = $userData['has_responses'];
            
            return $user;
        });
    }

    /**
     * Attach users to competition
     */
    private function attachUsersToCompetition(Competition $competition, \Illuminate\Support\Collection $users): void
    {
        $competition->users()->attach($users->pluck('id'));
        $this->command->info("   ✅ {$users->count()} users attached to competition");
    }

    /**
     * Attach admin to competition
     */
    private function attachAdminToCompetition(Competition $competition): void
    {
        $admin = Admin::factory()->create([
            'name' =>'ai-test-admin1',
            'email' => 'ai-test-admin1-with-responses@example.com',
            'password' => Hash::make('12345678'),
        ]);
        $competition->auditors()->attach([$this->admin->id,$admin->id]);
        $this->command->info("   ✅ Admin attached to competition");
    }

    /**
     * Create test responses for users who should have responses
     */
    private function createTestResponses(Level $level, \Illuminate\Support\Collection $questions, \Illuminate\Support\Collection $users): void
    {
        $usersWithResponses = $users->where('has_responses', true);
        $responseCount = 0;

        foreach ($usersWithResponses as $user) {
            foreach ($questions as $question) {
                $response = Response::create([
                    'user_id' => $user->id,
                    'question_id' => $question->id,
                    'response_text' => $this->generateRealisticResponse($question),
                    'response_duration' => rand(20, $question->duration),
                    'keystrokes' => rand(10, 50),
                    'penalty' => 0,
                    'score' => 0, // Will be set by AI auditing
                    'admin_id' => null, // AI generated
                    'flags' => json_encode([]),
                    'ai_generated' => false, // Will be set to true by AI auditing
                    'ai_score_generated_at' => null // Will be set by AI auditing
                ]);
                $responseCount++;
            }
        }

        $this->command->info("   ✅ {$responseCount} responses created for {$usersWithResponses->count()} users");
        $this->command->info("   ℹ️  {$users->where('has_responses', false)->count()} users have no responses (will test finish level behavior)");
    }

    /**
     * Generate realistic user responses for testing
     */
    private function generateRealisticResponse(Question $question): string
    {
        $responses = [
            'What is the capital of France?' => [
                'Paris',
                'Paris is the capital',
                'The capital is Paris',
                'France capital is Paris',
                'Paris, France',
                'It\'s Paris'
            ],
            'Who wrote Romeo and Juliet?' => [
                'Shakespeare',
                'William Shakespeare',
                'Shakespeare wrote it',
                'It was written by Shakespeare',
                'The author is Shakespeare',
                'Shakespeare is the writer'
            ],
            'What is the solution of 2x^2 + 4x - 6 = 0?' => [
                'x = 1 and x = -3',
                'The solution is x = 1 and x = -3',
                'The solution is x = 1 and x = -3',
                'no solution',
                'x = 1 and x = 4',
                'The answer is x = 2 and x = -3',
                'one'
            ],
            'Explain the concept of photosynthesis.' => [
                'Photosynthesis is how plants make food using sunlight',
                'Plants convert sunlight into energy through photosynthesis',
                'It\'s the process where plants use sun, CO2, and water to make glucose',
                'Photosynthesis allows plants to produce oxygen and glucose',
                'Plants use photosynthesis to create their own food from sunlight',
                'The process where plants turn light energy into chemical energy'
            ],
            'What is the largest planet in our solar system?' => [
                'Jupiter',
                'Jupiter is the biggest',
                'The largest planet is Jupiter',
                'Jupiter, the gas giant',
                'It\'s Jupiter',
                'Jupiter is the biggest planet'
            ]
        ];

        $questionText = $question->question_text;
        $availableResponses = $responses[$questionText] ?? ['Some answer'];
        
        return $availableResponses[array_rand($availableResponses)];
    }
} 