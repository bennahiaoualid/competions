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

} 