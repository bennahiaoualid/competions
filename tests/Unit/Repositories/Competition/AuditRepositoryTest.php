<?php

namespace Tests\Unit\Repositories\Competition;

use Tests\TestCase;
use App\Models\User;
use App\Models\Admin\Admin;
use App\Models\Competition\Level;
use App\Models\Competition\Question;
use App\Models\Competition\Response;
use App\Models\Competition\Competition;
use App\Repository\Competition\AuditRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Exception;

class AuditRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private AuditRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new AuditRepository();
    }

    public function test_get_competitions_for_audit_returns_filtered_and_paginated()
    {
        $admin = Admin::factory()->create();
        $this->be($admin, 'admin');
        $competitions = Competition::factory()->count(2)->create();
        $admin->competitionsAudit()->attach($competitions->pluck('id')->toArray());
        $result = $this->repository->getCompetitionsForAudit();
        $this->assertEquals(2, $result->total());
        foreach ($result as $competition) {
            $this->assertTrue($competitions->pluck('id')->contains($competition->id));
        }
    }

    public function test_get_level_questions_with_user_responses_returns_questions_with_responses()
    {
        $user = User::factory()->create();
        $level = Level::factory()->create();
        $questions = Question::factory()->count(2)->create(['level_id' => $level->id]);
        $responses = collect();
        foreach ($questions as $question) {
            $responses->push(Response::factory()->create([
                'user_id' => $user->id,
                'question_id' => $question->id,
            ]));
        }
        $result = $this->repository->getLevelQuestionsWithUserResponses($level->id, $user->id);
        $this->assertEquals(2, $result->total());
        foreach ($result as $question) {
            $this->assertTrue($questions->pluck('id')->contains($question->id));
            $this->assertEquals(1, $question->responses->count());
            $this->assertEquals($user->id, $question->responses->first()->user_id);
        }
    }

    public function test_is_admin_allowed_to_audit_user_returns_true_for_valid_assignment()
    {
        $admin = Admin::factory()->create();
        $user = User::factory()->create();
        $level = Level::factory()->create();
        DB::table('level_admin_user')->insert([
            'level_id' => $level->id,
            'user_id' => $user->id,
            'admin_id' => $admin->id,
        ]);
        $this->be($admin, 'admin');
        $result = $this->repository->isAdminAllowedToAuditUser($level, $user);
        $this->assertTrue($result);
    }

    public function test_is_admin_allowed_to_audit_user_returns_false_for_invalid_assignment()
    {
        $admin = Admin::factory()->create();
        $user = User::factory()->create();
        $level = Level::factory()->create();
        $this->be($admin, 'admin');
        $result = $this->repository->isAdminAllowedToAuditUser($level, $user);
        $this->assertFalse($result);
    }

    public function test_get_targeted_user_responses_returns_only_matching_responses()
    {
        $user = User::factory()->create();
        $level = Level::factory()->create();
        $questions = Question::factory()->count(2)->create(['level_id' => $level->id]);
        $responses = collect();
        foreach ($questions as $question) {
            $responses->push(Response::factory()->create([
                'user_id' => $user->id,
                'question_id' => $question->id,
                'admin_id' => null,
            ]));
        }
        $otherResponse = Response::factory()->create([
            'user_id' => $user->id,
            'question_id' => $questions[0]->id,
            'admin_id' => 1,
        ]);
        $responseIds = $responses->pluck('id')->flip()->toArray();
        $result = $this->repository->getTargetedUserResponses($level->id, $user->id, $responseIds);
        $this->assertCount(2, $result);
        foreach ($result as $response) {
            $this->assertNull($response->admin_id);
            $this->assertTrue($responses->pluck('id')->contains($response->id));
        }
    }


    public function test_update_response_scores_updates_scores_correctly()
    {
        $responses = Response::factory()->count(2)->create(['score' => 0]);
        $data = $responses->map(function ($response, $i) {
            return [
                'id' => $response->id,
                'score' => $i + 1,
            ];
        })->toArray();
        $result = $this->repository->updateResponseScores($data);
        $this->assertTrue($result);
        foreach ($responses as $i => $response) {
            $this->assertEquals($i + 1, $response->fresh()->score);
        }
    }

    public function test_assign_auditors_to_responses_for_level_updates_only_ai_generated_and_null_admin()
    {
        $level = Level::factory()->create();
        $questions = Question::factory()->count(2)->create(['level_id' => $level->id]);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Map admin to users for the level
        $admin1 = Admin::factory()->create();
        $admin2 = Admin::factory()->create();
        DB::table('level_admin_user')->insert([
            ['level_id' => $level->id, 'user_id' => $user1->id, 'admin_id' => $admin1->id],
            ['level_id' => $level->id, 'user_id' => $user2->id, 'admin_id' => $admin2->id],
        ]);

        // Eligible responses: ai_generated=true, admin_id=null
        $eligible1 = Response::factory()->create([
            'user_id' => $user1->id,
            'question_id' => $questions[0]->id,
            'ai_generated' => true,
            'admin_id' => null,
        ]);
        $eligible2 = Response::factory()->create([
            'user_id' => $user2->id,
            'question_id' => $questions[1]->id,
            'ai_generated' => true,
            'admin_id' => null,
        ]);

        // Not eligible: ai_generated=false
        $notEligible1 = Response::factory()->create([
            'user_id' => $user1->id,
            'question_id' => $questions[0]->id,
            'ai_generated' => false,
            'admin_id' => null,
        ]);
        // Not eligible: already has admin_id
        $notEligible2 = Response::factory()->create([
            'user_id' => $user2->id,
            'question_id' => $questions[1]->id,
            'ai_generated' => true,
            'admin_id' => $admin2->id,
        ]);
        // Different level question shouldn't be updated
        $otherLevel = Level::factory()->create();
        $otherQuestion = Question::factory()->create(['level_id' => $otherLevel->id]);
        $otherResponse = Response::factory()->create([
            'user_id' => $user1->id,
            'question_id' => $otherQuestion->id,
            'ai_generated' => true,
            'admin_id' => null,
        ]);

        $affected = $this->repository->assignAuditorsToResponsesForLevel($level->id);

        $this->assertEquals(2, $affected);
        $this->assertEquals($admin1->id, $eligible1->fresh()->admin_id);
        $this->assertEquals($admin2->id, $eligible2->fresh()->admin_id);
        $this->assertNull($notEligible1->fresh()->admin_id);
        $this->assertEquals($admin2->id, $notEligible2->fresh()->admin_id);
        $this->assertNull($otherResponse->fresh()->admin_id);
    }

    public function test_bulk_update_responses_updates_targeted_and_flags_empty_in_batch()
    {
        $level = Level::factory()->create();
        $questionA = Question::factory()->create(['level_id' => $level->id]);
        $questionB = Question::factory()->create(['level_id' => $level->id]);
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $outsideUser = User::factory()->create();

        // Targeted responses
        $r1 = Response::factory()->create([
            'user_id' => $userA->id,
            'question_id' => $questionA->id,
            'score' => 0,
            'final_score' => 0,
            'ai_generated' => false,
            'response_text' => 'ok',
            'admin_id' => null,
        ]);
        $r2 = Response::factory()->create([
            'user_id' => $userB->id,
            'question_id' => $questionA->id,
            'score' => 0,
            'final_score' => 0,
            'ai_generated' => false,
            'response_text' => 'ok',
            'admin_id' => null,
        ]);
        // Empty response in batch (should be zeroed)
        $emptyInBatch = Response::factory()->create([
            'user_id' => $userA->id,
            'question_id' => $questionB->id,
            'score' => 0,
            'final_score' => 5,
            'ai_generated' => false,
            'response_text' => '',
            'admin_id' => null,
        ]);
        // Already audited should not change in empty pass
        $admin_alreadyAudited = Admin::factory()->create();
        $alreadyAudited = Response::factory()->create([
            'user_id' => $userB->id,
            'question_id' => $questionB->id,
            'score' => 0,
            'final_score' => 7,
            'ai_generated' => false,
            'response_text' => '',
            'admin_id' => $admin_alreadyAudited->id,
        ]);
        // Outside batch user/question should not be affected
        $outside = Response::factory()->create([
            'user_id' => $outsideUser->id,
            'question_id' => $questionB->id,
            'score' => 0,
            'final_score' => 3,
            'ai_generated' => false,
            'response_text' => '',
            'admin_id' => null,
        ]);

        $bulkData = [
            ['id' => $r1->id, 'score' => 10, 'final_score' => 8],
            ['id' => $r2->id, 'score' => 20, 'final_score' => 18],
        ];
        $batchUserIds = [$userA->id, $userB->id];
        $batchQuestionIds = [$questionA->id, $questionB->id];

        // Execute
        $this->repository->bulkUpdateResponses($bulkData, $batchUserIds, $batchQuestionIds);

        // Assert targeted updates
        $this->assertEquals(10, $r1->fresh()->score);
        $this->assertEquals(8, $r1->fresh()->final_score);
        $this->assertTrue((bool)$r1->fresh()->ai_generated);
        $this->assertNotNull($r1->fresh()->ai_score_generated_at);

        $this->assertEquals(20, $r2->fresh()->score);
        $this->assertEquals(18, $r2->fresh()->final_score);
        $this->assertTrue((bool)$r2->fresh()->ai_generated);
        $this->assertNotNull($r2->fresh()->ai_score_generated_at);

        // Assert empty responses in batch are zeroed and flagged
        $this->assertEquals(0, $emptyInBatch->fresh()->final_score);
        $this->assertTrue((bool)$emptyInBatch->fresh()->ai_generated);
        $this->assertNotNull($emptyInBatch->fresh()->ai_score_generated_at);

        // Outside batch unaffected
        $this->assertEquals(3, $outside->fresh()->final_score);
        $this->assertFalse((bool)$outside->fresh()->ai_generated);

        // Already audited unchanged
        $this->assertEquals(7, $alreadyAudited->fresh()->final_score);
        $this->assertEquals($admin_alreadyAudited->id, $alreadyAudited->fresh()->admin_id);
    }
} 