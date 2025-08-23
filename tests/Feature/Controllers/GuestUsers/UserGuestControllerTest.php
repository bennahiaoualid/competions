<?php

namespace Tests\Feature\Controllers\GuestUsers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Admin\Admin;
use App\Enums\AISubjectEnum;
use App\Enums\AIDifficultyEnum;
use App\Jobs\Ai\GenerateAIQuestionJob;
use Database\Seeders\RoleSeeder;
use App\Models\GuestUsers\Choice;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Session;
use App\Models\GuestUsers\GlobalQuestion;
use App\Models\GuestUsers\GlobalResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserGuestControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Admin $admin;
    protected GlobalQuestion $question;
    protected Choice $correctChoice;
    protected Choice $incorrectChoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->refreshApplicationWithLocale('en');

        // Seed roles
        $this->seed(RoleSeeder::class);

        // Create admin
        $this->admin = Admin::factory()->create();
        $this->admin->assignRole('super_admin');

        // Create user
        $this->user = User::factory()->create();

        // Create approved question with choices
        $this->question = GlobalQuestion::factory()->create([
            'admin_id' => $this->admin->id,
            'approved' => $this->admin->id,
            'question_text' => 'Test question for guest users?',
            'score' => 10,
            'duration' => 60,
        ]);
        

        // Create correct choice
        $this->correctChoice = Choice::factory()->for($this->question,'question')->create([
            'choice_text' => 'Correct answer',
            'correct' => true,
        ]);
        
        // Create incorrect choice
        $this->incorrectChoice = Choice::factory()->create([
            'question_id' => $this->question->id,
            'choice_text' => 'Incorrect answer',
            'correct' => false,
        ]);
    }

    // ========================================
    // AUTHENTICATION & AUTHORIZATION TESTS
    // ========================================

    public function test_authenticated_routes_require_user_authentication()
    {
        $routes = [
            ['get', route('user.global_questions.response')],
            ['get', route('user.global_questions.response.ai')],
            ['post', route('user.global_questions.response.store')],
            ['get', route('user.global_questions.responses')],
            ['get', route('user.global_questions.ai_question_generation')],
            ['post', route('user.global_questions.ai_question_generation.store')],
        ];

        foreach ($routes as [$method, $uri]) {
            $response = $this->{$method}($uri);
            $response->assertRedirect(route('login'));
        }
    }

    public function test_public_routes_do_not_require_authentication()
    {
        $routes = [
            ['get', route('global_questions.index')],
            ['get', route('global_questions.global_order')],
        ];

        foreach ($routes as [$method, $uri]) {
            $response = $this->{$method}($uri);
            $response->assertOk();
        }
    }

    // ========================================
    // INDEX METHOD TESTS
    // ========================================

    public function test_user_can_access_prepare_page()
    {
        $response = $this->get(route('global_questions.index'));
        
        $response->assertOk();
        $response->assertViewIs('pages.user.guest_users.prepare');
    }

    // ========================================
    // GET RANDOM QUESTION TESTS
    // ========================================

    public function test_authenticated_user_can_get_random_question()
    {
        $this->actingAs($this->user);
        $response = $this->get(route('user.global_questions.response'));
        
        $response->assertOk();
        $response->assertViewIs('pages.user.guest_users.question_response');
        $response->assertViewHas('question');
        
        $question = $response->viewData('question');
        $this->assertInstanceOf(GlobalQuestion::class, $question);
        $this->assertTrue($question->choices->count() > 0);
    }

    public function test_get_random_question_creates_pending_response()
    {
        $this->actingAs($this->user);
        $this->get(route('user.global_questions.response'));
        
        $this->assertDatabaseHas('global_responses', [
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'choice_id' => null,
            'score' => 0,
            'response_duration' => 0,
        ]);
    }

    public function test_get_random_question_shows_no_question_when_no_eligible_questions()
    {
        $this->actingAs($this->user);
        
        // Delete all questions
        GlobalQuestion::query()->delete();
        
        $response = $this->get(route('user.global_questions.response'));
        
        $response->assertOk();
        $response->assertViewIs('pages.user.guest_users.no_question');
    }

    public function test_get_random_question_redirects_back_on_error()
    {
        $this->actingAs($this->user);
        
        // Mock service to return error
        $this->mock(\App\Services\GuestUsers\UserGuestService::class)
            ->shouldReceive('getRandomQuestion')
            ->once()
            ->andReturn(['status' => 'error']);
        
        $response = $this->get(route('user.global_questions.response'));
        
        $response->assertRedirect();
    }

    public function test_get_random_question_only_shows_approved_questions()
    {
        // Create unapproved question
        $unapprovedQuestion = GlobalQuestion::factory()->create([
            'approved' => null,
        ]);
        
        $this->actingAs($this->user);
        $response = $this->get(route('user.global_questions.response'));
        
        $response->assertOk();
        $question = $response->viewData('question');
        $this->assertNotNull($question->approved);
    }

    public function test_get_random_question_excludes_questions_user_has_answered_twice()
    {
        $this->actingAs($this->user);
        
        // Create two responses for the same question
        GlobalResponse::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'choice_id' => $this->correctChoice->id,
            'score' => 5,
        ]);
        
        GlobalResponse::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'choice_id' => $this->incorrectChoice->id,
            'score' => 0,
        ]);

        $response = $this->get(route('user.global_questions.response'));
        
        $response->assertOk();
        $response->assertViewIs('pages.user.guest_users.no_question');
    }

    public function test_get_random_question_falls_back_to_user_ai_question_when_no_approved_questions_available()
    {
        $this->actingAs($this->user);
        
        // Delete all approved questions
        GlobalQuestion::query()->delete();
        
        // Create user's AI question (unapproved, but user owns it)
        $aiQuestion = GlobalQuestion::factory()->create([
            'ai' => true,
            'user_id' => $this->user->id,
            'approved' => null, // Not approved yet
        ]);
        
        // Create choice for AI question
        $aiChoice = Choice::factory()->create([
            'question_id' => $aiQuestion->id,
            'choice_text' => 'AI Question Choice',
            'correct' => true,
        ]);
        
        $response = $this->get(route('user.global_questions.response'));
        
        $response->assertOk();
        $response->assertViewIs('pages.user.guest_users.question_response');
        $response->assertViewHas('question');
        
        $question = $response->viewData('question');
        $this->assertEquals($aiQuestion->id, $question->id);
        $this->assertTrue($question->ai);
        $this->assertEquals($this->user->id, $question->user_id);
        $this->assertNull($question->approved);
    }

    // ========================================
    // GET RANDOM AI QUESTION TESTS
    // ========================================

    public function test_get_random_ai_question_with_null_question_id_returns_user_ai_question_when_available()
    {
        $this->actingAs($this->user);
        
        // Create user's AI question
        $aiQuestion = GlobalQuestion::factory()->create([
            'ai' => true,
            'user_id' => $this->user->id,
            'approved' => null,
        ]);
        
        // Create choice for AI question
        $aiChoice = Choice::factory()->create([
            'question_id' => $aiQuestion->id,
            'choice_text' => 'AI Question Choice',
            'correct' => true,
        ]);
        
        $response = $this->get(route('user.global_questions.response.ai'));
        
        $response->assertOk();
        $response->assertViewIs('pages.user.guest_users.question_response');
        $response->assertViewHas('question');
        
        $question = $response->viewData('question');
        $this->assertEquals($aiQuestion->id, $question->id);
        $this->assertTrue($question->ai);
        $this->assertEquals($this->user->id, $question->user_id);
    }

    public function test_get_random_ai_question_with_null_question_id_returns_no_question_when_no_eligible_ai_questions()
    {
        $this->actingAs($this->user);
        
        // Create AI question for another user (not eligible for current user)
        $otherUser = User::factory()->create();
        $otherAiQuestion = GlobalQuestion::factory()->create([
            'ai' => true,
            'user_id' => $otherUser->id,
            'approved' => null,
        ]);
        
        $response = $this->get(route('user.global_questions.response.ai'));
        
        $response->assertOk();
        $response->assertViewIs('pages.user.guest_users.no_question');
    }

    public function test_get_random_ai_question_with_null_question_id_returns_no_question_when_user_has_answered_twice()
    {
        $this->actingAs($this->user);
        
        // Create user's AI question
        $aiQuestion = GlobalQuestion::factory()->create([
            'ai' => true,
            'user_id' => $this->user->id,
            'approved' => null,
        ]);
        
        // Create choice for AI question
        $aiChoice = Choice::factory()->create([
            'question_id' => $aiQuestion->id,
            'choice_text' => 'AI Question Choice',
            'correct' => true,
        ]);
        
        // Create two responses for the same question (making it ineligible)
        GlobalResponse::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $aiQuestion->id,
            'choice_id' => $aiChoice->id,
            'score' => 5,
        ]);
        
        GlobalResponse::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $aiQuestion->id,
            'choice_id' => $aiChoice->id,
            'score' => 3,
        ]);
        
        $response = $this->get(route('user.global_questions.response.ai'));
        
        $response->assertOk();
        $response->assertViewIs('pages.user.guest_users.no_question');
    }

    public function test_get_random_ai_question_with_specific_question_id_returns_exact_question_when_eligible()
    {
        $this->actingAs($this->user);
        
        // Create user's AI question
        $aiQuestion = GlobalQuestion::factory()->create([
            'ai' => true,
            'user_id' => $this->user->id,
            'approved' => null,
        ]);
        
        // Create choice for AI question
        $aiQuestion->choices()->create([
            'choice_text' => 'AI Question Choice',
            'correct' => true,
        ]);
        
        $response = $this->get(route('user.global_questions.response.ai', $aiQuestion->id));
        
        $response->assertOk();
        $response->assertViewIs('pages.user.guest_users.question_response');
        $response->assertViewHas('question');
        
        $question = $response->viewData('question');
        $this->assertEquals($aiQuestion->id, $question->id);
        $this->assertTrue($question->ai);
        $this->assertEquals($this->user->id, $question->user_id);
    }

    public function test_get_random_ai_question_with_specific_question_id_returns_exact_question_when_user_has_one_score_zero_response()
    {
        $this->actingAs($this->user);
        
        // Create user's AI question
        $aiQuestion = GlobalQuestion::factory()->create([
            'ai' => true,
            'user_id' => $this->user->id,
            'approved' => null,
        ]);
        
        // Create choice for AI question
        $aiChoice = Choice::factory()->create([
            'question_id' => $aiQuestion->id,
            'choice_text' => 'AI Question Choice',
            'correct' => true,
        ]);
        
        // Create exactly one response with score = 0 (making it eligible for retry)
        GlobalResponse::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $aiQuestion->id,
            'choice_id' => $aiChoice->id,
            'score' => 0,
        ]);
        
        $response = $this->get(route('user.global_questions.response.ai', $aiQuestion->id));
        
        $response->assertOk();
        $response->assertViewIs('pages.user.guest_users.question_response');
        $response->assertViewHas('question');
        
        $question = $response->viewData('question');
        $this->assertEquals($aiQuestion->id, $question->id);
    }

    public function test_get_random_ai_question_with_specific_question_id_returns_no_question_when_question_not_owned_by_user()
    {
        $this->actingAs($this->user);
        
        // Create AI question for another user
        $otherUser = User::factory()->create();
        $otherAiQuestion = GlobalQuestion::factory()->create([
            'ai' => true,
            'user_id' => $otherUser->id,
            'approved' => null,
        ]);
        
        $response = $this->get(route('user.global_questions.response.ai', $otherAiQuestion->id));
        
        $response->assertOk();
        $response->assertViewIs('pages.user.guest_users.no_question');
    }

    public function test_get_random_ai_question_with_specific_question_id_returns_no_question_when_user_has_answered_twice()
    {
        $this->actingAs($this->user);
        
        // Create user's AI question
        $aiQuestion = GlobalQuestion::factory()->create([
            'ai' => true,
            'user_id' => $this->user->id,
            'approved' => null,
        ]);
        
        // Create choice for AI question
        $aiChoice = Choice::factory()->create([
            'question_id' => $aiQuestion->id,
            'choice_text' => 'AI Question Choice',
            'correct' => true,
        ]);
        
        // Create two responses with scores > 0 (making it ineligible)
        GlobalResponse::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $aiQuestion->id,
            'choice_id' => $aiChoice->id,
            'score' => 5,
        ]);
        
        GlobalResponse::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $aiQuestion->id,
            'choice_id' => $aiChoice->id,
            'score' => 3,
        ]);
        
        $response = $this->get(route('user.global_questions.response.ai', $aiQuestion->id));
        
        $response->assertOk();
        $response->assertViewIs('pages.user.guest_users.no_question');
    }

    public function test_get_random_ai_question_with_nonexistent_question_id_returns_no_question()
    {
        $this->actingAs($this->user);
        
        $response = $this->get(route('user.global_questions.response.ai', 99999));
        
        $response->assertOk();
        $response->assertViewIs('pages.user.guest_users.no_question');
    }

    // ========================================
    // AI QUESTION GENERATION TESTS
    // ========================================

    public function test_ai_question_generation_page_is_accessible_to_authenticated_users()
    {
        $this->actingAs($this->user);
        
        $response = $this->get(route('user.global_questions.ai_question_generation'));
        
        $response->assertOk();
        $response->assertViewIs('pages.user.guest_users.ai_question_generation');
    }

    public function test_ai_question_generation_page_passes_correct_cost_data_to_view()
    {
        $this->actingAs($this->user);
        
        $response = $this->get(route('user.global_questions.ai_question_generation'));
        
        $response->assertOk();
        $response->assertViewIs('pages.user.guest_users.ai_question_generation');
        
        // Check that all three cost variables are passed to the view
        $response->assertViewHas('base_cost', null);
        $response->assertViewHas('difficulty_cost', null);
        $response->assertViewHas('subject_cost', null);
        
    }

    // ========================================
    // GENERATE AI QUESTION TESTS
    // ========================================

    public function test_generate_ai_question_requires_authentication()
    {
        $response = $this->post(route('user.global_questions.ai_question_generation.store'), [
            'subject' => 'math',
            'difficulty' => 'medium',
        ]);
        
        $response->assertRedirect(route('login'));
    }

    public function test_generate_ai_question_with_valid_data_returns_success()
    {
        Bus::fake();
        $this->actingAs($this->user);
        
        // Ensure user has enough coins for AI question generation
        $this->user->coinBalance()->create(['balance' => 100.0]);
        
        $response = $this->post(route('user.global_questions.ai_question_generation.store'), [
            'subject' => AISubjectEnum::MATHEMATICS->value,
            'difficulty' => AIDifficultyEnum::EASY->value,
        ]);
        
        $response->assertOk();
        
        // Check that the response is JSON
        $response->assertHeader('Content-Type', 'application/json');
        
        // Get the response data
        $responseData = $response->json();
        // Verify the response structure
        $this->assertArrayHasKey('success', $responseData);
        $this->assertArrayHasKey('data', $responseData);
        
        // The actual response will depend on the real AI service
        // We just verify the structure is correct
        $this->assertTrue($responseData['success']);
        // Verify that a job was dispatched to the queue
        Bus::assertDispatched(GenerateAIQuestionJob::class);
    }

    public function test_generate_ai_question_with_validation_errors_returns_error_response()
    {
        Bus::fake();
        $this->actingAs($this->user);
        
        $response = $this->post(route('user.global_questions.ai_question_generation.store'), [
            // Missing required fields
        ]);
        
        $response->assertStatus(422);
        
        // Check that the response is JSON
        $response->assertHeader('Content-Type', 'application/json');
        
        // Get the response data
        $responseData = $response->json();
        
        // Verify the error response structure
        $this->assertArrayHasKey('success', $responseData);
        $this->assertArrayHasKey('exception_type', $responseData);
        $this->assertArrayHasKey('error_type', $responseData);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertArrayHasKey('user_message', $responseData);
        $this->assertArrayHasKey('context', $responseData);
        
        // Verify the values
        $this->assertFalse($responseData['success']);
        $this->assertEquals('validation_error', $responseData['exception_type']);
        $this->assertEquals('field_validation_failed', $responseData['error_type']);
        
        // Check that validation errors are included
        $this->assertArrayHasKey('validation_errors', $responseData['context']);
        $this->assertArrayHasKey('fields', $responseData['context']);
        
        // The actual fields will depend on the real validation rules
        // We just verify the structure is correct
        $this->assertIsArray($responseData['context']['validation_errors']);
        $this->assertIsArray($responseData['context']['fields']);
        $this->assertEquals($responseData['context']['fields'],['subject', 'difficulty']);

        Bus::assertNotDispatched(GenerateAIQuestionJob::class);
    }

    public function test_generate_ai_question_with_insufficient_coins_returns_error()
    {
        Bus::fake();
        $this->actingAs($this->user);
        
        // Ensure user has insufficient coins
        $this->user->coinBalance()->create(['balance' => 0.0]);
        
        $response = $this->post(route('user.global_questions.ai_question_generation.store'), [
            'subject' => AISubjectEnum::MATHEMATICS->value,
            'difficulty' => AIDifficultyEnum::EASY->value,
        ]);
        
        $response->assertOk();
        
        // Check that the response is JSON
        $response->assertHeader('Content-Type', 'application/json');
        
        // Get the response data
        $responseData = $response->json();
        
        // Verify the error response structure
        $this->assertArrayHasKey('success', $responseData);
        $this->assertFalse($responseData['success']);
        
        // Should indicate insufficient coins
        $this->assertEquals('paid_service', $responseData['exception_type']);

        Bus::assertNotDispatched(GenerateAIQuestionJob::class);
    }


    // ========================================
    // STORE RESPONSE TESTS
    // ========================================

    public function test_authenticated_user_can_store_valid_response()
    {
        $this->actingAs($this->user);
        
        // Set session start time
        Session::put('start_time', now()->subSeconds(30));

        GlobalResponse::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'choice_id' => null,
            'score' => 0,
        ]);
        
        $response = $this->post(route('user.global_questions.response.store'), [
            'question_id' => $this->question->id,
            'choice_id' => $this->correctChoice->id,
        ]);
        
        $response->assertOk();
        $response->assertViewIs('pages.user.guest_users.response_score');
        $response->assertViewHas('data_result');
        
        $this->assertDatabaseHas('global_responses', [
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'choice_id' => $this->correctChoice->id,
        ]);
    }

    public function test_authenticated_user_can_not_store_valid_response_not_found_response_exception()
    {
        $this->actingAs($this->user);
        
        // Set session start time
        Session::put('start_time', now()->subSeconds(30));
        
        $response = $this->post(route('user.global_questions.response.store'), [
            'question_id' => $this->question->id,
            'choice_id' => $this->correctChoice->id,
        ]);
        
        $response->assertRedirect(route('global_questions.index'));
    }

    public function test_store_response_validates_required_fields()
    {
        $this->actingAs($this->user);
        
        $response = $this->post(route('user.global_questions.response.store'), []);

        $response->assertSessionHasErrors(['question_id', 'choice_id'], errorBag:'storeResponse');
    }

    public function test_store_response_validates_choice_belongs_to_question()
    {
        $this->actingAs($this->user);
        
        // Create another question with its own choice
        $otherQuestion = GlobalQuestion::factory()->create(['approved' => $this->admin->id]);
        $otherChoice = Choice::factory()->create(['question_id' => $otherQuestion->id]);
        
        $response = $this->post(route('user.global_questions.response.store'), [
            'question_id' => $this->question->id,
            'choice_id' => $otherChoice->id,
        ]);
        
        $response->assertRedirect(route('global_questions.index'));
    }

    public function test_store_response_calculates_score_correctly_for_correct_answer()
    {
        $this->actingAs($this->user);
        Session::put('start_time', now()->subSeconds(30));
        GlobalResponse::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'choice_id' => null,
            'score' => 0,
        ]);
        
        $response = $this->post(route('user.global_questions.response.store'), [
            'question_id' => $this->question->id,
            'choice_id' => $this->correctChoice->id,
        ]);
        
        $response->assertOk();
        
        $this->assertDatabaseHas('global_responses', [
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'choice_id' => $this->correctChoice->id,
            'score' => 7.5, 
        ]);
    }

    public function test_store_response_calculates_score_correctly_for_incorrect_answer()
    {
        $this->actingAs($this->user);
        Session::put('start_time', now()->subSeconds(60));
        GlobalResponse::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'choice_id' => null,
            'score' => 0,
        ]);
        
        $response = $this->post(route('user.global_questions.response.store'), [
            'question_id' => $this->question->id,
            'choice_id' => $this->incorrectChoice->id,
        ]);
        
        $response->assertOk();
        
        $this->assertDatabaseHas('global_responses', [
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'choice_id' => $this->incorrectChoice->id,
            'score' => 0.0,
        ]);
    }

    public function test_store_response_redirects_on_error()
    {
        $this->actingAs($this->user);
        
        // Mock service to return error
        $this->mock(\App\Services\GuestUsers\UserGuestService::class)
            ->shouldReceive('storeResponse')
            ->once()
            ->andReturn(['status' => 'error']);
        
        $response = $this->post(route('user.global_questions.response.store'), [
            'question_id' => $this->question->id,
            'choice_id' => $this->correctChoice->id,
        ]);
        
        $response->assertRedirect(route('global_questions.index'));
    }

    public function test_store_response_requires_session_start_time()
    {
        $this->actingAs($this->user);
        
        // Don't set session start time
        
        $response = $this->post(route('user.global_questions.response.store'), [
            'question_id' => $this->question->id,
            'choice_id' => $this->correctChoice->id,
        ]);
        
        $response->assertRedirect(route('global_questions.index'));
    }

    public function test_store_response_clears_session_start_time_after_success()
    {
        $this->actingAs($this->user);
        Session::put('start_time', now()->subSeconds(30));
        GlobalResponse::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'choice_id' => null,
            'score' => 0,
        ]);
        
        $this->post(route('user.global_questions.response.store'), [
            'question_id' => $this->question->id,
            'choice_id' => $this->correctChoice->id,
        ]);
        
        $this->assertNull(Session::get('start_time'));
    }

    // ========================================
    // GLOBAL USERS ORDER TESTS
    // ========================================

    public function test_user_can_view_global_users_order()
    {
        $response = $this->get(route('global_questions.global_order'));
        
        $response->assertOk();
        $response->assertViewIs('pages.user.guest_users.global_order');
    }

    // ========================================
    // GET GLOBAL USER RESPONSE TESTS
    // ========================================

    public function test_authenticated_user_can_view_their_responses()
    {
        $this->actingAs($this->user);
        
        // Create a response for the user
        GlobalResponse::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'choice_id' => $this->correctChoice->id,
        ]);
        
        $response = $this->get(route('user.global_questions.responses'));
        
        $response->assertOk();
        $response->assertViewIs('pages.user.guest_users.user_global_responses');
        $response->assertViewHas('questions');
    }

    public function test_get_global_user_response_shows_paginated_results()
    {
        $this->actingAs($this->user);
        
        // Create multiple responses
        for ($i = 0; $i < 15; $i++) {
            $question = GlobalQuestion::factory()->create(['approved' => $this->admin->id]);
            $choice = Choice::factory()->create(['question_id' => $question->id]);
            GlobalResponse::factory()->create([
                'user_id' => $this->user->id,
                'question_id' => $question->id,
                'choice_id' => $choice->id,
            ]);
        }
        
        $response = $this->get(route('user.global_questions.responses'));
        
        $response->assertOk();
        $questions = $response->viewData('questions');
        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $questions);
    }

    public function test_get_global_user_response_redirects_back_on_error()
    {
        $this->actingAs($this->user);
        
        // Mock service to return error
        $this->mock(\App\Services\GuestUsers\UserGuestService::class)
            ->shouldReceive('getGlobalUserResponse')
            ->once()
            ->andReturn(['status' => 'error']);
        
        $response = $this->get(route('user.global_questions.responses'));
        
        $response->assertRedirect();
    }

    public function test_get_global_user_response_only_shows_user_own_responses()
    {
        $this->actingAs($this->user);
        
        // Create response for another user
        $otherUser = User::factory()->create();
        GlobalResponse::factory()->create([
            'user_id' => $otherUser->id,
            'question_id' => $this->question->id,
            'choice_id' => $this->correctChoice->id,
        ]);
        
        $response = $this->get(route('user.global_questions.responses'));
        
        $response->assertOk();
        $questions = $response->viewData('questions');
        $this->assertEquals(0, $questions->count());
    }

    // ========================================
    // EDGE CASES AND ERROR HANDLING
    // ========================================

    public function test_guest_user_can_access_all_functionality()
    {
        $guestUser = User::factory()->create(['guest' => true]);
        $this->actingAs($guestUser);
        
        // Test public routes (no auth required)
        $response = $this->get(route('global_questions.index'));
        $response->assertOk();
        
        $response = $this->get(route('global_questions.global_order'));
        $response->assertOk();
        
        // Test authenticated routes
        $response = $this->get(route('user.global_questions.response'));
        $response->assertOk();
        
        // Test store response
        Session::put('start_time', now()->subSeconds(30));
        $response = $this->post(route('user.global_questions.response.store'), [
            'question_id' => $this->question->id,
            'choice_id' => $this->correctChoice->id,
        ]);
        $response->assertOk();
    }

    public function test_store_response_with_invalid_data_types()
    {
        $this->actingAs($this->user);
        
        $response = $this->post(route('user.global_questions.response.store'), [
            'question_id' => 'not-an-integer',
            'choice_id' => 'not-an-integer',
        ]);
        
        $response->assertSessionHasErrors(['question_id', 'choice_id'], errorBag:'storeResponse');
    }

    public function test_multiple_responses_for_same_question_are_handled_correctly()
    {
        $this->actingAs($this->user);
        
        // First response
        Session::put('start_time', now()->subSeconds(30));
        $this->post(route('user.global_questions.response.store'), [
            'question_id' => $this->question->id,
            'choice_id' => $this->incorrectChoice->id,
        ]);
        
        // Second response (should be allowed since first was wrong)
        Session::put('start_time', now()->subSeconds(20));
        $response = $this->get(route('user.global_questions.response'));
        $response->assertOk();
        
        $question = $response->viewData('question');
        $this->assertEquals($this->question->id, $question->id);
    }
} 