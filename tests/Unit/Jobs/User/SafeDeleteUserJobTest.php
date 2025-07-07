<?php

namespace Tests\Unit\Jobs\User;

use Tests\TestCase;
use App\Models\User;
use App\Models\Admin\Admin;
use App\Models\Competition\Competition;
use App\Models\Competition\Level;
use App\Models\Competition\Question;
use App\Models\Competition\Response;
use App\Jobs\User\SafeDeleteUserJob;
use App\Models\Monitoring\JobTracking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Exception;

class SafeDeleteUserJobTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Admin $admin;
    private Competition $activeCompetition;
    private Competition $pendingCompetition;
    private Competition $completedCompetition;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->admin = Admin::factory()->create();
        $this->user = User::factory()->create();
        
        // Create competitions with different statuses
        $this->activeCompetition = Competition::factory()->create([
            'status' => Competition::STATUS_ACTIVE,
            'admin_id' => $this->admin->id
        ]);
        
        $this->pendingCompetition = Competition::factory()->create([
            'status' => Competition::STATUS_PENDING,
            'admin_id' => $this->admin->id
        ]);
        
        $this->completedCompetition = Competition::factory()->create([
            'status' => Competition::STATUS_COMPLETED,
            'admin_id' => $this->admin->id
        ]);
        
        // Attach user to all competitions
        $this->activeCompetition->users()->attach($this->user->id);
        $this->pendingCompetition->users()->attach($this->user->id);
        $this->completedCompetition->users()->attach($this->user->id);
    }

    public function test_successfully_deletes_user_with_all_related_data()
    {
        Event::fake();
        
        // Create levels for active and pending competitions
        $activeLevel = Level::factory()->create([
            'competition_id' => $this->activeCompetition->id,
            'status' => Level::STATUS_ACTIVE
        ]);
        
        $pendingLevel = Level::factory()->create([
            'competition_id' => $this->pendingCompetition->id,
            'status' => Level::STATUS_PENDING
        ]);
        
        // Create questions for each level
        $activeQuestion = Question::factory()->create(['level_id' => $activeLevel->id]);
        $pendingQuestion = Question::factory()->create(['level_id' => $pendingLevel->id]);
        
        // Create responses for the user
        $activeResponse = Response::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $activeQuestion->id
        ]);
        
        $pendingResponse = Response::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $pendingQuestion->id
        ]);
        
        // Create level admin user relations
        \DB::table('level_admin_user')->insert([
            'level_id' => $activeLevel->id,
            'user_id' => $this->user->id,
            'admin_id' => $this->admin->id
        ]);
        
        // Execute the job
        $job = new SafeDeleteUserJob($this->user, $this->admin, 'Test deletion');
        $result = $job->handle();
        
        // Assert user is soft deleted
        $this->assertTrue($this->user->fresh()->trashed());
        
        // Assert user is removed from active and pending competitions (but not completed)
        $this->assertDatabaseMissing('competition_user', [
            'user_id' => $this->user->id,
            'competition_id' => $this->activeCompetition->id
        ]);
        
        $this->assertDatabaseMissing('competition_user', [
            'user_id' => $this->user->id,
            'competition_id' => $this->pendingCompetition->id
        ]);
        
        $this->assertDatabaseHas('competition_user', [
            'user_id' => $this->user->id,
            'competition_id' => $this->completedCompetition->id
        ]);
        
        // Assert level admin user relations are deleted for active competition
        $this->assertDatabaseMissing('level_admin_user', [
            'level_id' => $activeLevel->id,
            'user_id' => $this->user->id
        ]);
        
        // Assert responses are deleted for active competition only
        $this->assertDatabaseMissing('responses', [
            'id' => $activeResponse->id
        ]);
        
        $this->assertDatabaseHas('responses', [
            'id' => $pendingResponse->id
        ]);
        
        // Assert job tracking was created
        $this->assertDatabaseHas('job_tracking', [
            'user_id' => $this->admin->id,
            'entity_type' => 'User',
            'entity_id' => $this->user->id,
            'job_type' => 'user_deletion'
        ]);
        
        // Assert result structure
        $this->assertIsArray($result);
        $this->assertEquals($this->user->id, $result['user_id']);
        $this->assertEquals($this->user->name, $result['user']);
        $this->assertArrayHasKey('completed_at', $result);
    }

    public function test_handles_user_with_no_active_competitions()
    {
        Event::fake();
        
        // Remove user from active and pending competitions
        $this->activeCompetition->users()->detach($this->user->id);
        $this->pendingCompetition->users()->detach($this->user->id);
        
        $job = new SafeDeleteUserJob($this->user, $this->admin, 'Test deletion');
        $result = $job->handle();
        
        // Assert user is still soft deleted
        $this->assertTrue($this->user->fresh()->trashed());
        
        // Assert job completed successfully
        $this->assertIsArray($result);
        $this->assertEquals($this->user->id, $result['user_id']);
    }

    public function test_handles_user_with_no_related_data()
    {
        Event::fake();
        
        // Create a user with no competition associations
        $isolatedUser = User::factory()->create();
        
        $job = new SafeDeleteUserJob($isolatedUser, $this->admin, 'Test deletion');
        $result = $job->handle();
        
        // Assert user is soft deleted
        $this->assertTrue($isolatedUser->fresh()->trashed());
        
        // Assert job completed successfully
        $this->assertIsArray($result);
        $this->assertEquals($isolatedUser->id, $result['user_id']);
    }

    public function test_restores_user_on_failure()
    {
        Event::fake();
        Log::fake();
        
        // Mock the user to throw an exception during deletion
        /** @var User $mockUser */
        $mockUser = \Mockery::mock($this->user)->makePartial();
        $mockUser->shouldReceive('delete')->once()->andThrow(new Exception('Database error'));
        
        $job = new SafeDeleteUserJob($mockUser, $this->admin, 'Test deletion');
        
        // The job should handle the exception and restore the user
        $job->handle();
        
        // Assert user is not deleted (restored after failure)
        $this->assertFalse($this->user->fresh()->trashed());
        
        // Assert error was logged
        Log::assertLogged('error', function ($message, $context) {
            return $message === 'SafeDeleteUserJob failed' && 
                   is_array($context) && isset($context['user_id']) && $context['user_id'] === $this->user->id;
        });
    }

    public function test_from_tracking_payload_creates_job_correctly()
    {
        // Create a job tracking record
        $tracking = JobTracking::factory()->create([
            'user_id' => $this->admin->id,
            'entity_type' => 'User',
            'entity_id' => $this->user->id,
            'job_type' => 'user_deletion',
            'payload' => [
                'user_id' => $this->user->id,
                'initiator_id' => $this->admin->id,
                'reason' => 'Test reason'
            ]
        ]);
        
        $payload = [
            'user_id' => $this->user->id,
            'initiator_id' => $this->admin->id,
            'reason' => 'Test reason'
        ];
        
        $job = SafeDeleteUserJob::fromTrackingPayload($payload, $this->admin->id, $tracking->id);
        
        $this->assertInstanceOf(SafeDeleteUserJob::class, $job);
        $this->assertEquals($this->user->id, $job->getUser()->id);
    }

    public function test_from_tracking_payload_returns_null_when_user_not_found()
    {
        Log::fake();
        
        $payload = [
            'user_id' => 99999, // Non-existent user
            'initiator_id' => $this->admin->id,
            'reason' => 'Test reason'
        ];
        
        $job = SafeDeleteUserJob::fromTrackingPayload($payload, $this->admin->id, 'tracking-id');
        
        $this->assertNull($job);
        
        Log::assertLogged('warning', function ($message, $context) {
            return $message === 'SafeDeleteUserJob retrying failed: user not found or admin not found' &&
                   is_array($context);
        });
    }

    public function test_from_tracking_payload_returns_null_when_admin_not_found()
    {
        Log::fake();
        
        $payload = [
            'user_id' => $this->user->id,
            'initiator_id' => 99999, // Non-existent admin
            'reason' => 'Test reason'
        ];
        
        $job = SafeDeleteUserJob::fromTrackingPayload($payload, 99999, 'tracking-id');
        
        $this->assertNull($job);
        
        Log::assertLogged('warning', function ($message, $context) {
            return $message === 'SafeDeleteUserJob retrying failed: user not found or admin not found' &&
                   is_array($context);
        });
    }

    public function test_preserves_completed_competition_data()
    {
        Event::fake();
        
        // Create level and question for completed competition
        $completedLevel = Level::factory()->create([
            'competition_id' => $this->completedCompetition->id,
            'status' => Level::STATUS_FINISHED
        ]);
        
        $completedQuestion = Question::factory()->create(['level_id' => $completedLevel->id]);
        
        // Create response for completed competition
        $completedResponse = Response::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $completedQuestion->id
        ]);
        
        // Create level admin user relation for completed competition
        \DB::table('level_admin_user')->insert([
            'level_id' => $completedLevel->id,
            'user_id' => $this->user->id,
            'admin_id' => $this->admin->id
        ]);
        
        $job = new SafeDeleteUserJob($this->user, $this->admin, 'Test deletion');
        $job->handle();
        
        // Assert user is still associated with completed competition
        $this->assertDatabaseHas('competition_user', [
            'user_id' => $this->user->id,
            'competition_id' => $this->completedCompetition->id
        ]);
        
        // Assert level admin user relation is preserved for completed competition
        $this->assertDatabaseHas('level_admin_user', [
            'level_id' => $completedLevel->id,
            'user_id' => $this->user->id
        ]);
        
        // Assert response is preserved for completed competition
        $this->assertDatabaseHas('responses', [
            'id' => $completedResponse->id
        ]);
    }

    public function test_handles_multiple_active_competitions()
    {
        Event::fake();
        
        // Create another active competition
        $activeCompetition2 = Competition::factory()->create([
            'status' => Competition::STATUS_ACTIVE,
            'admin_id' => $this->admin->id
        ]);
        $activeCompetition2->users()->attach($this->user->id);
        
        // Create levels and responses for both active competitions
        $level1 = Level::factory()->create(['competition_id' => $this->activeCompetition->id]);
        $level2 = Level::factory()->create(['competition_id' => $activeCompetition2->id]);
        
        $question1 = Question::factory()->create(['level_id' => $level1->id]);
        $question2 = Question::factory()->create(['level_id' => $level2->id]);
        
        $response1 = Response::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $question1->id
        ]);
        
        $response2 = Response::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $question2->id
        ]);
        
        $job = new SafeDeleteUserJob($this->user, $this->admin, 'Test deletion');
        $job->handle();
        
        // Assert user is removed from both active competitions
        $this->assertDatabaseMissing('competition_user', [
            'user_id' => $this->user->id,
            'competition_id' => $this->activeCompetition->id
        ]);
        
        $this->assertDatabaseMissing('competition_user', [
            'user_id' => $this->user->id,
            'competition_id' => $activeCompetition2->id
        ]);
        
        // Assert responses are deleted from both active competitions
        $this->assertDatabaseMissing('responses', ['id' => $response1->id]);
        $this->assertDatabaseMissing('responses', ['id' => $response2->id]);
    }
} 