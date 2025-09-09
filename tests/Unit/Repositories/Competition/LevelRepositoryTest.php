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

    public function test_get_response_audit_counts_with_ai_on_and_off_and_empty_dataset()
    {
        $competition = Competition::factory()->create();
        $level = Level::factory()->create(['competition_id' => $competition->id]);
        $questions = Question::factory()->count(2)->create(['level_id' => $level->id]);

        $users = User::factory()->count(4)->create();

        // Create responses covering all combinations
        // non-AI, admin assigned => audited
        Response::factory()->create([
            'user_id' => $users[0]->id,
            'question_id' => $questions[0]->id,
            'admin_id' => Admin::factory()->create()->id,
            'ai_generated' => false,
        ]);

        // non-AI, admin null => not_audited
        Response::factory()->create([
            'user_id' => $users[1]->id,
            'question_id' => $questions[0]->id,
            'admin_id' => null,
            'ai_generated' => false,
        ]);

        // AI, admin assigned => confirmed
        Response::factory()->create([
            'user_id' => $users[2]->id,
            'question_id' => $questions[1]->id,
            'admin_id' => Admin::factory()->create()->id,
            'ai_generated' => true,
        ]);

        // AI, admin null => not_confirmed
        Response::factory()->create([
            'user_id' => $users[3]->id,
            'question_id' => $questions[1]->id,
            'admin_id' => null,
            'ai_generated' => true,
        ]);

        // With AI auditing ON
        $withAi = $this->repository->getResponseAuditCounts($level->id, true);
        $this->assertEquals(1, $withAi['audited']);
        $this->assertEquals(1, $withAi['not_audited']);
        $this->assertEquals(1, $withAi['confirmed']);
        $this->assertEquals(1, $withAi['not_confirmed']);

        // With AI auditing OFF (ignore ai_generated flag)
        $withoutAi = $this->repository->getResponseAuditCounts($level->id, false);
        $this->assertEquals(2, $withoutAi['audited']); // two with admin_id not null
        $this->assertEquals(2, $withoutAi['not_audited']); // two with admin_id null
        $this->assertEquals(0, $withoutAi['confirmed']);
        $this->assertEquals(0, $withoutAi['not_confirmed']);

        // Empty dataset for another level should return zeros
        $emptyLevel = Level::factory()->create(['competition_id' => $competition->id]);
        $emptyCountsWithAi = $this->repository->getResponseAuditCounts($emptyLevel->id, true);
        $this->assertEquals(0, $emptyCountsWithAi['audited']);
        $this->assertEquals(0, $emptyCountsWithAi['not_audited']);
        $this->assertEquals(0, $emptyCountsWithAi['confirmed']);
        $this->assertEquals(0, $emptyCountsWithAi['not_confirmed']);

        $emptyCountsWithoutAi = $this->repository->getResponseAuditCounts($emptyLevel->id, false);
        $this->assertEquals(0, $emptyCountsWithoutAi['audited']);
        $this->assertEquals(0, $emptyCountsWithoutAi['not_audited']);
    }

    public function test_reassign_users_responses_auditing_permission_updates_only_unaudited_users_and_returns_count()
    {
        $competition = Competition::factory()->create();
        $level = Level::factory()->create(['competition_id' => $competition->id]);
        $questions = Question::factory()->count(2)->create(['level_id' => $level->id]);

        $creator = Admin::factory()->create();

        $users = User::factory()->count(3)->create();
        $auditorA = Admin::factory()->create();
        $auditorB = Admin::factory()->create();

        // User 0: has at least one unaudited response (admin_id null)
        Response::factory()->create([
            'user_id' => $users[0]->id,
            'question_id' => $questions[0]->id,
            'admin_id' => null,
        ]);
        // User 1: all responses audited
        Response::factory()->create([
            'user_id' => $users[1]->id,
            'question_id' => $questions[0]->id,
            'admin_id' => $auditorA->id,
        ]);
        // User 2: at least one unaudited response
        Response::factory()->create([
            'user_id' => $users[2]->id,
            'question_id' => $questions[1]->id,
            'admin_id' => null,
        ]);

        // Seed pivot with initial assignments
        DB::table('level_admin_user')->insert([
            [ 'level_id' => $level->id, 'user_id' => $users[0]->id, 'admin_id' => $auditorA->id ],
            [ 'level_id' => $level->id, 'user_id' => $users[1]->id, 'admin_id' => $auditorA->id ],
            [ 'level_id' => $level->id, 'user_id' => $users[2]->id, 'admin_id' => $auditorB->id ],
        ]);

        $updated = $this->repository->reAssignUsersResponsesAudtingPermission($level->id, $creator->id);
        $this->assertEquals(2, $updated); // users[0] and users[2]

        $pivot = DB::table('level_admin_user')->where('level_id', $level->id)->get()->keyBy('user_id');
        $this->assertEquals($creator->id, $pivot[$users[0]->id]->admin_id);
        $this->assertEquals($auditorA->id, $pivot[$users[1]->id]->admin_id); // unchanged
        $this->assertEquals($creator->id, $pivot[$users[2]->id]->admin_id);
    }

    public function test_reassign_users_responses_auditing_permission_returns_zero_when_no_unaudited_users()
    {
        $competition = Competition::factory()->create();
        $level = Level::factory()->create(['competition_id' => $competition->id]);
        $questions = Question::factory()->count(2)->create(['level_id' => $level->id]);

        $creator = Admin::factory()->create();
        $auditor = Admin::factory()->create();
        $users = User::factory()->count(2)->create();

        // All responses audited
        foreach ($users as $user) {
            foreach ($questions as $question) {
                Response::factory()->create([
                    'user_id' => $user->id,
                    'question_id' => $question->id,
                    'admin_id' => $auditor->id,
                ]);
            }
        }

        // Seed pivot
        foreach ($users as $user) {
            DB::table('level_admin_user')->insert([
                'level_id' => $level->id,
                'user_id' => $user->id,
                'admin_id' => $auditor->id,
            ]);
        }

        $updated = $this->repository->reAssignUsersResponsesAudtingPermission($level->id, $creator->id);
        $this->assertEquals(0, $updated);

        // Ensure no changes occurred
        $pivot = DB::table('level_admin_user')->where('level_id', $level->id)->pluck('admin_id');
        foreach ($pivot as $adminId) {
            $this->assertEquals($auditor->id, $adminId);
        }
    }
} 