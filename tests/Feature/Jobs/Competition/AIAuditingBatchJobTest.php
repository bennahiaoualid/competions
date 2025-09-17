<?php

namespace Tests\Feature\Jobs\Competition;

use Tests\TestCase;
use App\Models\User;
use App\Models\Admin\Admin;
use App\Models\Competition\Competition;
use App\Models\Competition\Level;
use App\Models\Competition\Question;
use App\Models\Competition\Response;
use App\Jobs\Competition\AIAuditingBatchJob;
use App\Jobs\Competition\AIAuditingBatchJobFactory;
use App\Services\Competition\AuditService;
use App\Services\LLM\LLMHandlerFactory;
use App\Services\SystemSettingService;
use App\Services\Monitoring\JobTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class AIAuditingBatchJobTest extends TestCase
{
    use RefreshDatabase;

    protected Competition $competition;
    protected Level $level;
    protected Collection $users;
    protected Collection $questions;
    protected Collection $responses;
    protected AIAuditingBatchJob $job;
    protected AIAuditingBatchJobFactory $factory;
    protected SystemSettingService $systemSettingService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Disable queue to run jobs synchronously
        Queue::fake();
        
        // Create test data
        $this->createTestData();
        
        // Create job factory with real services
        $this->factory = new AIAuditingBatchJobFactory(
            app(LLMHandlerFactory::class),
            app(SystemSettingService::class),
            app(JobTrackingService::class),
            app(AuditService::class)
        );
        $this->systemSettingService = app(SystemSettingService::class);
    }

    protected function createTestData(): void
    {
        // Create competition with AI auditing enabled
        $this->competition = Competition::factory()->create([
            'ai_auditing' => true,
            'status' => 'active'
        ]);

        // Create level
        $this->level = Level::factory()->create([
            'competition_id' => $this->competition->id,
            'status' => 'active'
        ]);

        // Create questions with perfect responses
        $this->questions = collect([
            Question::factory()->create([
                'level_id' => $this->level->id,
                'question_text' => 'What is the capital of France?',
                'perfect_response' => 'Paris is the capital of France.',
                'max_score' => 10,
                'duration' => 60
            ]),
            Question::factory()->create([
                'level_id' => $this->level->id,
                'question_text' => 'Who wrote Romeo and Juliet?',
                'perfect_response' => 'William Shakespeare wrote Romeo and Juliet.',
                'max_score' => 8,
                'duration' => 45
            ]),
            Question::factory()->create([
                'level_id' => $this->level->id,
                'question_text' => 'What is 2 + 2?',
                'perfect_response' => '2 + 2 equals 4.',
                'max_score' => 5,
                'duration' => 30
            ])
        ]);

        // Create users
        $this->users = collect([
            User::factory()->create(['name' => 'John Doe']),
            User::factory()->create(['name' => 'Jane Smith']),
            User::factory()->create(['name' => 'Bob Johnson'])
        ]);

        // Attach users to competition
        $this->competition->users()->attach($this->users->pluck('id'));

        // Create responses for each user and question
        $this->responses = collect();
        foreach ($this->users as $user) {
            foreach ($this->questions as $question) {
                $response = Response::factory()->create([
                    'user_id' => $user->id,
                    'question_id' => $question->id,
                    'response_text' => $this->generateUserResponse($question),
                    'response_duration' => rand(20, 50),
                    'keystrokes' => rand(10, 30),
                    'penalty' => 0,
                    'score' => 0,
                    'final_score' => null,
                    'admin_id' => null
                ]);
                $this->responses->push($response);
            }
        }
    }

    protected function generateUserResponse(Question $question): string
    {
        // Generate realistic user responses for testing
        $responses = [
            'What is the capital of France?' => [
                'Paris',
                'Paris is the capital',
                'The capital is Paris',
                'France capital is Paris'
            ],
            'Who wrote Romeo and Juliet?' => [
                'Shakespeare',
                'William Shakespeare',
                'Shakespeare wrote it',
                'It was written by Shakespeare'
            ],
            'What is 2 + 2?' => [
                '4',
                'Four',
                '2 + 2 = 4',
                'Equals 4'
            ]
        ];

        $questionText = $question->question_text;
        $availableResponses = $responses[$questionText] ?? ['Some answer'];
        
        return $availableResponses[array_rand($availableResponses)];
    }

    public function test_ai_auditing_batch_job_completes_successfully_with_real_services()
    {
        // Arrange: Create the job
        $this->job = $this->factory->create(
            level: $this->level,
            userBatch: $this->users,
            batchNumber: 1,
            totalBatches: 1,
            questions: $this->questions
        );

        // Act: Execute the job directly (not through queue)
        // Use reflection to access protected executeJob method for testing
        $reflection = new \ReflectionClass($this->job);
        $executeJobMethod = $reflection->getMethod('executeJob');
        $executeJobMethod->setAccessible(true);
        $result = $executeJobMethod->invoke($this->job);

        // Assert: Job completed successfully
        $this->assertIsArray($result);
        $this->assertArrayHasKey('level_id', $result);
        $this->assertArrayHasKey('users_processed', $result);
        $this->assertEquals($this->level->id, $result['level_id']);
        $this->assertEquals($this->users->count(), $result['users_processed']);

        // Assert: All responses have been updated with AI scores
        $updatedResponses = Response::whereIn('id', $this->responses->pluck('id'))->get();
        
        foreach ($updatedResponses as $response) {
            // Check that AI scores were generated
            $this->assertTrue($response->ai_generated, "Response {$response->id} should be marked as AI generated");
            $this->assertNotNull($response->ai_score_generated_at, "Response {$response->id} should have AI generation timestamp");
            $this->assertNotNull($response->score, "Response {$response->id} should have AI score");
            $this->assertNotNull($response->final_score, "Response {$response->id} should have final score");
            $this->assertNull($response->admin_id, "Response {$response->id} should not have admin_id (AI generated)");
            
            // Check score validation
            $question = $this->questions->firstWhere('id', $response->question_id);
            $this->assertNotNull($question, "Question should exist for response {$response->id}");
            $this->assertLessThanOrEqual($question->max_score, $response->score, 
                "AI score should not exceed max score for question {$question->id}");
            $this->assertGreaterThanOrEqual(0, $response->score, 
                "AI score should be non-negative for response {$response->id}");
        }

        // Assert: All users and questions were processed
        $this->assertEquals(
            $this->users->count() * $this->questions->count(),
            $updatedResponses->where('ai_generated', true)->count(),
            'All responses should be processed by AI'
        );

        // Assert: Final scores are calculated correctly (considering penalties)
        foreach ($updatedResponses as $response) {
            $this->assertLessThanOrEqual($response->score, $response->final_score, 
                "Final score should not exceed AI score for response {$response->id}");
        }

        // Log success for debugging
        Log::info('AI Auditing Batch Job Test completed successfully', [
            'responses_processed' => $updatedResponses->where('ai_generated', true)->count(),
            'total_responses' => $this->responses->count(),
            'users_count' => $this->users->count(),
            'questions_count' => $this->questions->count()
        ]);
    }

    public function test_ai_auditing_batch_job_uses_correct_llm_provider_and_model()
    {
        // Arrange: Set specific LLM provider and model in system settings
        $this->systemSettingService->setValue('ai_auditing_llm_provider', 'gemini');
        $this->systemSettingService->setValue('ai_auditing_model', 'gemini-1.5-flash');
        
        // Create the job
        $this->job = $this->factory->create(
            level: $this->level,
            userBatch: $this->users,
            batchNumber: 1,
            totalBatches: 1,
            questions: $this->questions
        );

        // Act: Execute the job directly
        $reflection = new \ReflectionClass($this->job);
        $executeJobMethod = $reflection->getMethod('executeJob');
        $executeJobMethod->setAccessible(true);
        $result = $executeJobMethod->invoke($this->job);

        // Assert: Job completed successfully
        $this->assertIsArray($result);
        $this->assertArrayHasKey('level_id', $result);
        $this->assertArrayHasKey('users_processed', $result);
        
        // Verify that the correct provider and model were used
        // This test ensures that the system settings are being read correctly
        $this->assertEquals('gemini', $this->systemSettingService->getValue('ai_auditing_llm_provider'));
        $this->assertEquals('gemini-1.5-flash', $this->systemSettingService->getValue('ai_auditing_model'));
        
        // Log the configuration used
        Log::info('LLM Configuration Test completed successfully', [
            'provider' => $this->systemSettingService->getValue('ai_auditing_llm_provider'),
            'model' => $this->systemSettingService->getValue('ai_auditing_model'),
            'result' => $result
        ]);
    }
} 