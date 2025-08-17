<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Models\User;
use App\Models\GuestUsers\GlobalQuestion;
use App\Models\GuestUsers\GlobalResponse;
use App\Models\GuestUsers\Choice;
use App\Repository\GuestUsers\UserGuestRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserGuestRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private UserGuestRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new UserGuestRepository();
    }

    public function test_get_latest_pending_response_returns_latest_pending()
    {
        $user = User::factory()->create();
        $question = GlobalQuestion::factory()->approved()->create();
        $pending1 = GlobalResponse::factory()->for($user)->for($question, 'question')->create(['choice_id' => null, 'created_at' => now()->subMinute()]);
        $pending2 = GlobalResponse::factory()->for($user)->for($question, 'question')->create(['choice_id' => null, 'created_at' => now()]);
        $found = $this->repository->getLatestPendingResponse($question->id, $user->id);
        $this->assertEquals($pending2->id, $found->id);
    }

    public function test_get_latest_pending_response_returns_null_if_none()
    {
        $user = User::factory()->create();
        $question = GlobalQuestion::factory()->approved()->create();
        $found = $this->repository->getLatestPendingResponse($question->id, $user->id);
        $this->assertNull($found);
    }

    public function test_get_user_responded_questions_paginated_returns_only_responded()
    {
        $user = User::factory()->create();
        $question1 = GlobalQuestion::factory()->approved()->create();
        $question2 = GlobalQuestion::factory()->approved()->create();
        $question3 = GlobalQuestion::factory()->approved()->create();
        GlobalResponse::factory()->for($user)->for($question1,'question')->create();
        GlobalResponse::factory()->for($user)->for($question2,'question')->create();
        $result = $this->repository->getUserRespondedQuestionsPaginated($user->id);
        $ids = $result->pluck('id')->toArray();
        $this->assertContains($question1->id, $ids);
        $this->assertContains($question2->id, $ids);
        $this->assertNotContains($question3->id, $ids);
    }

    public function test_get_user_responded_questions_paginated_returns_empty_if_none()
    {
        $user = User::factory()->create();
        $result = $this->repository->getUserRespondedQuestionsPaginated($user->id);
        $this->assertEquals(0, $result->total());
    }

    public function test_get_random_eligible_question_for_user_returns_approved_non_ai_question()
    {
        $user = User::factory()->create();
        $approvedQuestion = GlobalQuestion::factory()->approved()->create(['ai' => false]);
        $choice = Choice::factory()->create(['question_id' => $approvedQuestion->id, 'correct' => true]);
        
        $result = $this->repository->getRandomEligibleQuestionForUser($user->id);
        
        $this->assertNotNull($result);
        $this->assertEquals($approvedQuestion->id, $result->id);
        $this->assertFalse($result->ai);
        $this->assertNotNull($result->approved);
    }

    public function test_get_random_eligible_question_for_user_returns_ai_question_when_no_approved_available()
    {
        $user = User::factory()->create();
        $aiQuestion = GlobalQuestion::factory()->create(['ai' => true, 'user_id' => $user->id, 'approved' => null]);
        $choice = Choice::factory()->create(['question_id' => $aiQuestion->id, 'correct' => true]);
        
        $result = $this->repository->getRandomEligibleQuestionForUser($user->id);
        
        $this->assertNotNull($result);
        $this->assertEquals($aiQuestion->id, $result->id);
        $this->assertTrue($result->ai);
        $this->assertEquals($user->id, $result->user_id);
    }

    public function test_get_random_eligible_question_for_user_excludes_questions_with_score_greater_than_zero()
    {
        $user = User::factory()->create();
        $question = GlobalQuestion::factory()->approved()->create(['ai' => false]);
        $choice = Choice::factory()->create(['question_id' => $question->id, 'correct' => true]);
        
        // Create a response with score > 0
        GlobalResponse::factory()->for($user)->for($question, 'question')->create(['score' => 5]);
        
        $result = $this->repository->getRandomEligibleQuestionForUser($user->id);
        
        $this->assertNull($result);
    }

    public function test_get_random_eligible_question_for_user_includes_questions_with_score_zero()
    {
        $user = User::factory()->create();
        $question = GlobalQuestion::factory()->approved()->create(['ai' => false]);
        $choice = Choice::factory()->create(['question_id' => $question->id, 'correct' => true]);
        
        // Create exactly one response with score = 0
        GlobalResponse::factory()->for($user)->for($question, 'question')->create(['score' => 0]);
        
        $result = $this->repository->getRandomEligibleQuestionForUser($user->id);
        
        $this->assertNotNull($result);
        $this->assertEquals($question->id, $result->id);
    }

    public function test_get_random_ai_question_for_user_returns_user_specific_ai_question()
    {
        $user = User::factory()->create();
        $aiQuestion = GlobalQuestion::factory()->create(['ai' => true, 'user_id' => $user->id]);
        $choice = Choice::factory()->create(['question_id' => $aiQuestion->id, 'correct' => true]);
        
        $result = $this->repository->getRandomAIQuestionForUser($user->id);
        
        $this->assertNotNull($result);
        $this->assertEquals($aiQuestion->id, $result->id);
        $this->assertTrue($result->ai);
        $this->assertEquals($user->id, $result->user_id);
    }

    public function test_get_random_ai_question_for_user_with_specific_question_id()
    {
        $user = User::factory()->create();
        $aiQuestion = GlobalQuestion::factory()->create(['ai' => true, 'user_id' => $user->id]);
        $choice = Choice::factory()->create(['question_id' => $aiQuestion->id, 'correct' => true]);
        
        $result = $this->repository->getRandomAIQuestionForUser($user->id, $aiQuestion->id);
        
        $this->assertNotNull($result);
        $this->assertEquals($aiQuestion->id, $result->id);
    }

    public function test_get_random_ai_question_for_user_returns_null_for_nonexistent_question_id()
    {
        $user = User::factory()->create();
        
        $result = $this->repository->getRandomAIQuestionForUser($user->id, 99999);
        
        $this->assertNull($result);
    }

    public function test_get_random_premium_question_for_user_returns_question_after_48_hours()
    {
        $user = User::factory()->create();
        $question = GlobalQuestion::factory()->approved()->create([
            'ai' => true,
            'created_at' => now()->subHours(49)
        ]);
        $choice = Choice::factory()->create(['question_id' => $question->id, 'correct' => true]);
        
        $result = $this->repository->getRandomPremiumQuestionForUser($user->id);
        
        $this->assertNotNull($result);
        $this->assertEquals($question->id, $result->id);
        $this->assertTrue($result->ai);
        $this->assertNotNull($result->approved);
    }

    public function test_get_random_premium_question_for_user_excludes_questions_before_48_hours()
    {
        $user = User::factory()->create();
        $question = GlobalQuestion::factory()->approved()->create([
            'ai' => true,
            'created_at' => now()->subHours(47)
        ]);
        $choice = Choice::factory()->create(['question_id' => $question->id, 'correct' => true]);
        
        $result = $this->repository->getRandomPremiumQuestionForUser($user->id);
        
        $this->assertNull($result);
    }

    public function test_get_random_premium_question_for_user_excludes_questions_with_score_greater_than_zero()
    {
        $user = User::factory()->create();
        $question = GlobalQuestion::factory()->approved()->create([
            'ai' => true,
            'created_at' => now()->subHours(49)
            ]);
        $choice = Choice::factory()->create(['question_id' => $question->id, 'correct' => true]);
        
        // Create a response with score > 0
        GlobalResponse::factory()->for($user)->for($question, 'question')->create(['score' => 5]);
        
        $result = $this->repository->getRandomPremiumQuestionForUser($user->id);
        
        $this->assertNull($result);
    }
} 