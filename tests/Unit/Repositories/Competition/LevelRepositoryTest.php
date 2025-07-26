<?php

namespace Tests\Unit\Repositories\Competition;

use Mockery;
use Exception;
use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User;
use App\Models\Admin\Admin;
use App\Models\Competition\Level;
use Illuminate\Support\Facades\DB;
use App\Models\Competition\Question;
use App\Models\Competition\Response;
use App\Models\Competition\Competition;
use App\Repository\Competition\LevelRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LevelRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private LevelRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new LevelRepository();
    }

    
    public function test_detects_time_conflict_when_new_level_overlaps_with_existing()
    {
        // Create a competition
        $competition = Competition::factory()->create();

        // Create an existing level
        $existingLevel = Level::factory()->create([
            'competition_id' => $competition->id,
            'start_date' => Carbon::parse('2024-03-01 10:00:00'),
            'duration' => 60, // 1 hour
        ]);

        // Create a new level
        Level::factory()->create([
            'competition_id' => $competition->id,
            'start_date' => Carbon::parse('2024-03-01 13:00:00'),
            'duration' => 60, // 1 hour
        ]);

        // Test case 1: New level starts during existing level
        $this->assertTrue($this->repository->hasTimeConflict(
            $competition->id,
            '2024-03-01 10:30:00',
            30,
            null
        ));

        // Test case 2: New level ends during existing level
        $this->assertTrue($this->repository->hasTimeConflict(
            $competition->id,
            '2024-03-01 09:30:00',
            60,
            null
        ));

        // Test case 3: New level completely contains existing level
        $this->assertTrue($this->repository->hasTimeConflict(
            $competition->id,
            '2024-03-01 09:30:00',
            120,
            null
        ));

        // Test case 4: No conflict
        $this->assertFalse($this->repository->hasTimeConflict(
            $competition->id,
            '2024-03-01 11:30:00',
            30,
            null
        ));

        // Test case 5: No conflict when excluding the level being updated
        $this->assertFalse($this->repository->hasTimeConflict(
            $competition->id,
            '2024-03-01 10:30:00',
            30,
            $existingLevel->id
        ));

        // Test case 6: Conflict when updating a level with overlapping time
        $this->assertTrue($this->repository->hasTimeConflict(
            $competition->id,
            '2024-03-01 12:01:00',
            $existingLevel->duration,
            $existingLevel->id
        ));

    }

    
    public function test_inserts_missing_responses_for_level()
    {
        // Create test data
        $competition = Competition::factory()->create();
        $level = Level::factory()->create(['competition_id' => $competition->id]);
        $users = User::factory()->count(3)->create();
        $questions = Question::factory()->count(2)->create(['level_id' => $level->id]);

        // Create competition-user relationships
        $competition->users()->attach($users->pluck('id')->toArray());

        // Create one existing response
        Response::factory()->create([
            'user_id' => $users[0]->id,
            'question_id' => $questions[0]->id,
        ]);

        // Execute the method
        $this->repository->insertMissingResponsesForLevel($level, 100);

        // Assert that all missing responses were created
        $expectedResponseCount = (count($users) * count($questions));
        $this->assertEquals($expectedResponseCount, Response::count());

        // Verify that each user has responses for all questions except the one that already had a response
        foreach ($users as $user) {
            $userResponses = Response::where('user_id', $user->id)->get();
            if ($user->id === $users[0]->id) {
                $this->assertEquals(count($questions), $userResponses->count());
            } else {
                $this->assertEquals(count($questions), $userResponses->count());
            }
        }
    }

    
    public function test_assigns_auditors_to_users_in_pivot()
    {
        // Create test data
        $level = Level::factory()->create();
        $users = User::factory()->count(5)->create();
        $auditors = Admin::factory()->count(2)->create();

        // Execute the method
        $this->repository->assignAuditorsToUsersInPivot($level, $users, $auditors);

        // Assert that all users have been assigned to auditors
        $this->assertEquals($users->count(), DB::table('level_admin_user')->count());

        // Verify that auditors are distributed evenly
        $assignments = DB::table('level_admin_user')
            ->where('level_id', $level->id)
            ->get()
            ->groupBy('admin_id');

        // Each auditor should have approximately equal number of assignments
        $expectedMinAssignments = floor($users->count() / $auditors->count());
        $expectedMaxAssignments = ceil($users->count() / $auditors->count());

        foreach ($assignments as $adminAssignments) {
            $this->assertGreaterThanOrEqual($expectedMinAssignments, $adminAssignments->count());
            $this->assertLessThanOrEqual($expectedMaxAssignments, $adminAssignments->count());
        }

        // Verify that each user is assigned to exactly one auditor
        $userAssignments = DB::table('level_admin_user')
            ->where('level_id', $level->id)
            ->get()
            ->groupBy('user_id');

        foreach ($userAssignments as $userAssignment) {
            $this->assertEquals(1, $userAssignment->count());
        }

    }
} 