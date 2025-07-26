<?php

namespace Tests\Unit\Repositories\User;

use Tests\TestCase;
use App\Models\User;
use App\Models\Competition\Level;
use App\Models\Competition\Question;
use App\Models\Competition\Response;
use App\Repository\User\UserCompetitionRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Exception;

class UserCompetitionRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private UserCompetitionRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new UserCompetitionRepository();
    }

    public function test_get_unanswered_questions_returns_only_unanswered()
    {
        $user = User::factory()->create();
        $level = Level::factory()->create();
        $questions = Question::factory()->count(3)->create(['level_id' => $level->id]);
        // User answered the first question
        Response::factory()->create([
            'user_id' => $user->id,
            'question_id' => $questions[0]->id,
        ]);
        $unanswered = $this->repository->getUnansweredQuestions($level->id, $user->id);
        $this->assertCount(2, $unanswered);
        $this->assertTrue($unanswered->pluck('id')->contains($questions[1]->id));
        $this->assertTrue($unanswered->pluck('id')->contains($questions[2]->id));
    }

    public function test_get_unanswered_questions_returns_all_if_none_answered()
    {
        $user = User::factory()->create();
        $level = Level::factory()->create();
        $questions = Question::factory()->count(2)->create(['level_id' => $level->id]);
        $unanswered = $this->repository->getUnansweredQuestions($level->id, $user->id);
        $this->assertCount(2, $unanswered);
    }

    public function test_get_unanswered_questions_returns_empty_if_all_answered()
    {
        $user = User::factory()->create();
        $level = Level::factory()->create();
        $questions = Question::factory()->count(2)->create(['level_id' => $level->id]);
        foreach ($questions as $question) {
            Response::factory()->create([
                'user_id' => $user->id,
                'question_id' => $question->id,
            ]);
        }
        $unanswered = $this->repository->getUnansweredQuestions($level->id, $user->id);
        $this->assertCount(0, $unanswered);
    }

    public function test_update_response_successfully_updates_response()
    {
        $user = User::factory()->create();
        $this->be($user); // Set as authenticated user
        $question = Question::factory()->create();
        $response = Response::factory()->create([
            'user_id' => $user->id,
            'question_id' => $question->id,
            'response_text' => 'old',
        ]);
        $result = $this->repository->updateResponse($question->id, ['response_text' => 'new']);
        $this->assertTrue($result);
        $this->assertEquals('new', $response->fresh()->response_text);
    }

    public function test_update_response_throws_exception_if_response_not_found()
    {
        $user = User::factory()->create();
        $this->be($user);
        $question = Question::factory()->create();
        $this->expectException(Exception::class);
        $this->repository->updateResponse($question->id, ['answer' => 'new']);
    }

    public function test_get_user_level_responses_returns_paginated_responses()
    {
        $user = User::factory()->create();
        $level = Level::factory()->create();
        $questions = Question::factory()->count(3)->create(['level_id' => $level->id]);
        foreach ($questions as $question) {
            Response::factory()->create([
                'user_id' => $user->id,
                'question_id' => $question->id,
            ]);
        }
        $result = $this->repository->getUserLevelResponses($level->id, $user->id);
        $this->assertEquals(3, $result->total());
        foreach ($result as $response) {
            $this->assertEquals($user->id, $response->user_id);
            $this->assertEquals($level->id, $response->question->level_id);
        }
    }

    public function test_get_user_level_responses_returns_empty_if_none()
    {
        $user = User::factory()->create();
        $level = Level::factory()->create();
        $result = $this->repository->getUserLevelResponses($level->id, $user->id);
        $this->assertEquals(0, $result->total());
    }
} 