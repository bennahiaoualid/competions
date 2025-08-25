<?php

namespace Tests\Feature\Jobs\Competition;

use Tests\TestCase;
use App\Models\User;
use App\Models\Admin\Admin;
use App\Models\Competition\Level;
use App\Models\Competition\Question;
use App\Models\Competition\Response;
use App\Models\Competition\Competition;
use App\Jobs\Competition\FinishLevelTrackableJob;
use App\Interface\Competition\LevelRepositoryInterface;
use App\Services\Notification\OptimizedCompetitionNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mockery;

class FinishLevelTrackableJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_finish_level_job_execution_flow()
    {
        // Prepare data
        Bus::fake(); // Prevent actual job dispatching
        
        // Create admin who owns the competition
        $admin = Admin::factory()->create([
            'name' => 'Competition Owner',
            'email' => 'owner@test.com'
        ]);

        // Create auditors (admins who will audit the level)
        $auditor1 = Admin::factory()->create(['name' => 'Auditor 1']);
        $auditor2 = Admin::factory()->create(['name' => 'Auditor 2']);

        // Create users (competitors)
        $user1 = User::factory()->create(['name' => 'Competitor 1']);
        $user2 = User::factory()->create(['name' => 'Competitor 2']);
        $user3 = User::factory()->create(['name' => 'Competitor 3']);
        $user4 = User::factory()->create(['name' => 'Competitor 4']);

        // Create competition
        $competition = Competition::factory()->create([
            'title' => 'Test Competition',
            'admin_id' => $admin->id,
            'status' => Competition::STATUS_ACTIVE
        ]);

        // Create level that's ready to be finished
        $level = Level::factory()->create([
            'name' => 'Test Level',
            'competition_id' => $competition->id,
            'admin_id' => $admin->id,
            'questions_number' => 3,
            'start_date' => now()->subHours(3), // Started 3 hours ago
            'duration' => 120, // 2 hours duration
            'status' => Level::STATUS_ACTIVE // Currently active
        ]);

        // Create questions for the level
        $question1 = Question::factory()->create(['level_id' => $level->id]);
        $question2 = Question::factory()->create(['level_id' => $level->id]);
        $question3 = Question::factory()->create(['level_id' => $level->id]);

        // Attach users to competition
        $competition->users()->attach([$user1->id, $user2->id, $user3->id, $user4->id]);

        // Attach auditors to competition
        $competition->auditors()->attach([$auditor1->id, $auditor2->id]);

        // Create some existing responses (some users have answered some questions)
        Response::factory()->create([
            'question_id' => $question1->id,
            'user_id' => $user1->id,
            'response_text' => 'User 1 answered question 1'
        ]);

        Response::factory()->create([
            'question_id' => $question2->id,
            'user_id' => $user2->id,
            'response_text' => 'User 2 answered question 2'
        ]);

        // Mock the notification service to avoid dispatching notification jobs
        $mockNotificationService = Mockery::mock(OptimizedCompetitionNotificationService::class);

        $mockNotificationService->shouldReceive('levelFinished')
        ->once()
        ->with(Mockery::type(Competition::class), Mockery::type(Level::class));
        
        // Get real repository for actual database operations
        $levelRepository = app(LevelRepositoryInterface::class);

        // Create job
        $job = new FinishLevelTrackableJob(
            $level,
            $levelRepository,
            $mockNotificationService,
            userId: $admin->id
        );
        // Run job handle using reflection to access protected method
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('executeJob');
        $method->setAccessible(true);
        $result = $method->invoke($job);



        // Assert results
        // 1. insertMissingResponsesForLevel - all users have responses to the level questions
        $this->assertEquals(12, Response::count()); // 3 questions × 4 users = 12 responses
        
        // Verify missing responses were created
        $this->assertDatabaseHas('responses', [
            'question_id' => $question1->id,
            'user_id' => $user3->id,
            'response_text' => ''
        ]);

        $this->assertDatabaseHas('responses', [
            'question_id' => $question2->id,
            'user_id' => $user1->id,
            'response_text' => ''
        ]);

        $this->assertDatabaseHas('responses', [
            'question_id' => $question3->id,
            'user_id' => $user1->id,
            'response_text' => ''
        ]);

        $this->assertDatabaseHas('responses', [
            'question_id' => $question1->id,
            'user_id' => $user4->id,
            'response_text' => ''
        ]);

        

        // 2. assignUsersToAuditors - users are divided to auditors number
        $this->assertEquals(2, 
                DB::table('level_admin_user')
                    ->where('level_id', $level->id)
                    ->where('admin_id', $auditor1->id)
                    ->count()
            );
        $this->assertEquals(2, 
                DB::table('level_admin_user')
                    ->where('level_id', $level->id)
                    ->where('admin_id', $auditor2->id)
                    ->count()
            );

        // Verify all users are assigned
        $this->assertEquals(4, DB::table('level_admin_user')->where('level_id', $level->id)->count());

        // 3. level updated to finished
        $this->assertEquals(Level::STATUS_FINISHED, $level->fresh()->status);

        // 4. Verify result data
        $this->assertEquals($level->id, $result['level_id']);
        $this->assertEquals($level->name, $result['level_name']);
        $this->assertEquals($competition->title, $result['competition']);
        $this->assertArrayHasKey('completed_at', $result);

        // 5. Verify cache was cleared
        $cacheKey = 'assigned_user_count_admin_' . $admin->id;
        $this->assertFalse(Cache::has($cacheKey));

        // 6. Verify UserNotifyEmail::auditorsFinishLevel was called (via alias)
        // Note: The static method call in the job will dispatch email jobs, but we faked the bus
        
        // 7. Verify notification service was called
        // Note: The expectation was already set above, Mockery will verify it automatically
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
} 