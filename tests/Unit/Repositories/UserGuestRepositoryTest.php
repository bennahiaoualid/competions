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
        
        // Create exactly one response with score = 0 AND choice_id = null (void response)
        GlobalResponse::factory()->for($user)->for($question, 'question')->create([
            'choice_id' => null,
            'score' => 0
        ]);
        
        $result = $this->repository->getRandomEligibleQuestionForUser($user->id);
        
        $this->assertNotNull($result);
        $this->assertEquals($question->id, $result->id);
    }

    public function test_get_random_eligible_question_for_user_includes_questions_with_void_response()
    {
        $user = User::factory()->create();
        $question = GlobalQuestion::factory()->approved()->create(['ai' => false]);
        $choice = Choice::factory()->create(['question_id' => $question->id, 'correct' => true]);
        
        // Create exactly one void response (choice_id = null, score = 0)
        GlobalResponse::factory()->for($user)->for($question, 'question')->create([
            'choice_id' => null, 
            'score' => 0
        ]);
        
        $result = $this->repository->getRandomEligibleQuestionForUser($user->id);
        
        $this->assertNotNull($result);
        $this->assertEquals($question->id, $result->id);
    }

    public function test_get_random_eligible_question_for_user_includes_questions_with_two_responses_score_zero()
    {
        $user = User::factory()->create();
        $question = GlobalQuestion::factory()->approved()->create(['ai' => false]);
        $choice = Choice::factory()->create(['question_id' => $question->id, 'correct' => true]);
        
        // Create two responses with score = 0 (one void + one answered)
        GlobalResponse::factory()->for($user)->for($question, 'question')->create([
            'choice_id' => null, 
            'score' => 0
        ]);
        GlobalResponse::factory()->for($user)->for($question, 'question')->create([
            'choice_id' => $choice->id, 
            'score' => 0
        ]);
        
        $result = $this->repository->getRandomEligibleQuestionForUser($user->id);
        
        $this->assertNotNull($result);
        $this->assertEquals($question->id, $result->id);
    }

    public function test_get_random_eligible_question_for_user_excludes_questions_with_three_responses()
    {
        $user = User::factory()->create();
        $question = GlobalQuestion::factory()->approved()->create(['ai' => false]);
        $choice = Choice::factory()->create(['question_id' => $question->id, 'correct' => true]);
        
        // Create three responses with score = 0
        GlobalResponse::factory()->for($user)->for($question, 'question')->create([
            'choice_id' => null, 
            'score' => 0
        ]);
        GlobalResponse::factory()->for($user)->for($question, 'question')->create([
            'choice_id' => $choice->id, 
            'score' => 0
        ]);
        GlobalResponse::factory()->for($user)->for($question, 'question')->create([
            'choice_id' => $choice->id, 
            'score' => 0
        ]);
        
        $result = $this->repository->getRandomEligibleQuestionForUser($user->id);
        
        $this->assertNull($result);
    }

    public function test_get_random_eligible_question_for_user_excludes_questions_with_multiple_void_responses()
    {
        $user = User::factory()->create();
        $question = GlobalQuestion::factory()->approved()->create(['ai' => false]);
        $choice = Choice::factory()->create(['question_id' => $question->id, 'correct' => true]);
        
        // Create two void responses (should be blocked)
        GlobalResponse::factory()->for($user)->for($question, 'question')->create([
            'choice_id' => null, 
            'score' => 0
        ]);
        GlobalResponse::factory()->for($user)->for($question, 'question')->create([
            'choice_id' => null, 
            'score' => 0
        ]);
        
        $result = $this->repository->getRandomEligibleQuestionForUser($user->id);
        
        $this->assertNull($result);
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

    public function test_get_random_ai_question_for_user_includes_questions_with_void_response()
    {
        $user = User::factory()->create();
        $question = GlobalQuestion::factory()->create(['ai' => true, 'user_id' => $user->id]);
        $choice = Choice::factory()->create(['question_id' => $question->id, 'correct' => true]);
        
        // Create exactly one void response (choice_id = null, score = 0)
        GlobalResponse::factory()->for($user)->for($question, 'question')->create([
            'choice_id' => null, 
            'score' => 0
        ]);
        
        $result = $this->repository->getRandomAIQuestionForUser($user->id);
        
        $this->assertNotNull($result);
        $this->assertEquals($question->id, $result->id);
    }

    public function test_get_random_ai_question_for_user_includes_questions_with_two_responses_score_zero()
    {
        $user = User::factory()->create();
        $question = GlobalQuestion::factory()->create(['ai' => true, 'user_id' => $user->id]);
        $choice = Choice::factory()->create(['question_id' => $question->id, 'correct' => true]);
        
        // Create two responses with score = 0 (one void + one answered)
        $res1 = GlobalResponse::factory()->for($user)->for($question, 'question')->create([
            'choice_id' => null, 
            'score' => 0
        ]);
        $res2 = GlobalResponse::factory()->for($user)->for($question, 'question')->create([
            'choice_id' => $choice->id, 
            'score' => 0
        ]);
        
        $result = $this->repository->getRandomAIQuestionForUser($user->id);
        
        $this->assertNotNull($result);
        $this->assertEquals($question->id, $result->id);

        // two void responses
        $res2->update(['choice_id' => null]);
        $result = $this->repository->getRandomAIQuestionForUser($user->id);
        
        $this->assertNull($result);

         // two responses with score 0
        $res1->update(['choice_id' => $choice->id]);
        $res2->update(['choice_id' => $choice->id]);
        $result = $this->repository->getRandomAIQuestionForUser($user->id);
        
        $this->assertNotNull($result);
        $this->assertEquals($question->id, $result->id);
    }

    public function test_get_random_ai_question_for_user_includes_questions_with_three_responses_score_zero()
    {
        $user = User::factory()->create();
        $question = GlobalQuestion::factory()->create(['ai' => true, 'user_id' => $user->id]);
        $choice = Choice::factory()->create(['question_id' => $question->id, 'correct' => true]);
        
        // Create three responses with score = 0 (one void + two answered) - AI questions get extra chance
        $res1 = GlobalResponse::factory()->for($user)->for($question, 'question')->create([
            'choice_id' => null, 
            'score' => 0
        ]);
        $res2 = GlobalResponse::factory()->for($user)->for($question, 'question')->create([
            'choice_id' => $choice->id, 
            'score' => 0
        ]);
        $res3 = GlobalResponse::factory()->for($user)->for($question, 'question')->create([
            'choice_id' => $choice->id, 
            'score' => 0
        ]);
        
        $result = $this->repository->getRandomAIQuestionForUser($user->id);
        
        $this->assertNotNull($result);
        $this->assertEquals($question->id, $result->id);

        // two responses void with response score 0
        $res2->update(['choice_id' => null]);
        $result = $this->repository->getRandomAIQuestionForUser($user->id);
        
        $this->assertNull($result);
    }

    public function test_get_random_ai_question_for_user_excludes_questions_with_four_responses()
    {
        $user = User::factory()->create();
        $question = GlobalQuestion::factory()->create(['ai' => true, 'user_id' => $user->id]);
        $choice = Choice::factory()->create(['question_id' => $question->id, 'correct' => true]);
        
        // Create four responses with score = 0 (should be blocked)
        GlobalResponse::factory()->for($user)->for($question, 'question')->create([
            'choice_id' => null, 
            'score' => 0
        ]);
        GlobalResponse::factory()->for($user)->for($question, 'question')->create([
            'choice_id' => $choice->id, 
            'score' => 0
        ]);
        GlobalResponse::factory()->for($user)->for($question, 'question')->create([
            'choice_id' => $choice->id, 
            'score' => 0
        ]);
        GlobalResponse::factory()->for($user)->for($question, 'question')->create([
            'choice_id' => $choice->id, 
            'score' => 0
        ]);
        
        $result = $this->repository->getRandomAIQuestionForUser($user->id);
        
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

    public function test_get_random_premium_question_for_user_includes_questions_with_void_response()
    {
        $user = User::factory()->create();
        $question = GlobalQuestion::factory()->approved()->create([
            'ai' => true,
            'created_at' => now()->subHours(49)
        ]);
        $choice = Choice::factory()->create(['question_id' => $question->id, 'correct' => true]);
        
        // Create exactly one void response (choice_id = null, score = 0)
        GlobalResponse::factory()->for($user)->for($question, 'question')->create([
            'choice_id' => null, 
            'score' => 0
        ]);
        
        $result = $this->repository->getRandomPremiumQuestionForUser($user->id);
        
        $this->assertNotNull($result);
        $this->assertEquals($question->id, $result->id);
    }

    public function test_get_random_premium_question_for_user_includes_questions_with_two_responses_score_zero()
    {
        $user = User::factory()->create();
        $question = GlobalQuestion::factory()->approved()->create([
            'ai' => true,
            'created_at' => now()->subHours(49)
        ]);
        $choice = Choice::factory()->create(['question_id' => $question->id, 'correct' => true]);
        
        // Create two responses with score = 0 (one void + one answered)
        GlobalResponse::factory()->for($user)->for($question, 'question')->create([
            'choice_id' => null, 
            'score' => 0
        ]);
        GlobalResponse::factory()->for($user)->for($question, 'question')->create([
            'choice_id' => $choice->id, 
            'score' => 0
        ]);
        
        $result = $this->repository->getRandomPremiumQuestionForUser($user->id);
        
        $this->assertNotNull($result);
        $this->assertEquals($question->id, $result->id);
    }

    public function test_get_random_premium_question_for_user_excludes_questions_with_three_responses()
    {
        $user = User::factory()->create();
        $question = GlobalQuestion::factory()->approved()->create([
            'ai' => true,
            'created_at' => now()->subHours(49)
        ]);
        $choice = Choice::factory()->create(['question_id' => $question->id, 'correct' => true]);
        
        // Create three responses with score = 0 (should be blocked for premium questions)
        GlobalResponse::factory()->for($user)->for($question, 'question')->create([
            'choice_id' => null, 
            'score' => 0
        ]);
        GlobalResponse::factory()->for($user)->for($question, 'question')->create([
            'choice_id' => $choice->id, 
            'score' => 0
        ]);
        GlobalResponse::factory()->for($user)->for($question, 'question')->create([
            'choice_id' => $choice->id, 
            'score' => 0
        ]);
        
        $result = $this->repository->getRandomPremiumQuestionForUser($user->id);
        
        $this->assertNull($result);
    }

    public function test_get_random_premium_question_for_user_excludes_questions_with_multiple_void_responses()
    {
        $user = User::factory()->create();
        $question = GlobalQuestion::factory()->approved()->create([
            'ai' => true,
            'created_at' => now()->subHours(49)
        ]);
        $choice = Choice::factory()->create(['question_id' => $question->id, 'correct' => true]);
        
        // Create two void responses (should be blocked)
        GlobalResponse::factory()->for($user)->for($question, 'question')->create([
            'choice_id' => null, 
            'score' => 0
        ]);
        GlobalResponse::factory()->for($user)->for($question, 'question')->create([
            'choice_id' => null, 
            'score' => 0
        ]);
        
        $result = $this->repository->getRandomPremiumQuestionForUser($user->id);
        
        $this->assertNull($result);
    }

    public function test_get_eligible_ai_question_count_for_user_returns_correct_count()
    {
        $user = User::factory()->create();
        
        // Create 3 AI questions for the user
        $question1 = GlobalQuestion::factory()->create(['ai' => true, 'user_id' => $user->id]);
        $question2 = GlobalQuestion::factory()->create(['ai' => true, 'user_id' => $user->id]);
        $question3 = GlobalQuestion::factory()->create(['ai' => true, 'user_id' => $user->id]);
        
        // Add choices to each question
        Choice::factory()->create(['question_id' => $question1->id, 'correct' => true]);
        Choice::factory()->create(['question_id' => $question2->id, 'correct' => true]);
        Choice::factory()->create(['question_id' => $question3->id, 'correct' => true]);
        
        // Initially, all 3 questions should be eligible
        $count = $this->repository->getEligibleAIQuestionCountForUser($user->id);
        $this->assertEquals(3, $count);
        
        // Add a response to question1 (score = 0, choice_id = null - void response)
        GlobalResponse::factory()->for($user)->for($question1, 'question')->create([
            'choice_id' => null,
            'score' => 0
        ]);
        
        // Now question1 should still be eligible (void response), count should still be 3
        $count = $this->repository->getEligibleAIQuestionCountForUser($user->id);
        $this->assertEquals(3, $count);
        
        // Add a response to question2 (score = 0, with choice_id - answered)
        GlobalResponse::factory()->for($user)->for($question2, 'question')->create([
            'choice_id' => Choice::factory()->create(['question_id' => $question2->id])->id,
            'score' => 0
        ]);
        
        // Now question2 should still be eligible, count should still be 3
        $count = $this->repository->getEligibleAIQuestionCountForUser($user->id);
        $this->assertEquals(3, $count);
        
        // Add a response to question3 (score > 0 - correct answer)
        GlobalResponse::factory()->for($user)->for($question3, 'question')->create([
            'choice_id' => Choice::factory()->create(['question_id' => $question3->id])->id,
            'score' => 5
        ]);
        
        // Now question3 should NOT be eligible (correct answer), count should be 2
        $count = $this->repository->getEligibleAIQuestionCountForUser($user->id);
        $this->assertEquals(2, $count);
    }

    public function test_get_eligible_ai_question_count_for_user_returns_zero_when_no_questions()
    {
        $user = User::factory()->create();
        
        // User has no AI questions
        $count = $this->repository->getEligibleAIQuestionCountForUser($user->id);
        $this->assertEquals(0, $count);
    }

    public function test_get_eligible_ai_question_count_for_user_excludes_other_users_questions()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        // Create AI questions for both users
        $question1 = GlobalQuestion::factory()->create(['ai' => true, 'user_id' => $user1->id]);
        $question2 = GlobalQuestion::factory()->create(['ai' => true, 'user_id' => $user2->id]);
        
        Choice::factory()->create(['question_id' => $question1->id, 'correct' => true]);
        Choice::factory()->create(['question_id' => $question2->id, 'correct' => true]);
        
        // User1 should only see their own question
        $count1 = $this->repository->getEligibleAIQuestionCountForUser($user1->id);
        $this->assertEquals(1, $count1);
        
        // User2 should only see their own question
        $count2 = $this->repository->getEligibleAIQuestionCountForUser($user2->id);
        $this->assertEquals(1, $count2);
    }
} 