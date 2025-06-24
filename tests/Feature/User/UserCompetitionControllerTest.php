<?php

namespace Tests\Feature\User;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User;
use App\Models\Admin\Admin;
use App\Models\Competition\Level;
use Illuminate\Support\Facades\App;
use App\Models\Competition\Question;
use App\Models\Competition\Response;
use App\Models\Competition\Competition;
use Illuminate\Support\Facades\Session;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mcamara\LaravelLocalization\LaravelLocalization;

class UserCompetitionControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected User $guest_user;
    protected Admin $admin;
    protected Competition $competition;
    protected Level $level;
    protected Question $question;

    protected function setUp(): void
    {
        parent::setUp();
        $this->refreshApplicationWithLocale('en');
        
        // Create test data
        $this->admin = Admin::factory()->create();
        $this->user = User::factory()->create();
        $this->guest_user = User::factory()->create(['guest' => true]);
        $this->competition = Competition::factory()->create([
            'admin_id' => $this->admin->id,
            'status' => 1, // Active competition
            'start_date' => now()->subDays(1),
        ]);
        
        $this->level = Level::factory()->create([
            'competition_id' => $this->competition->id,
            'admin_id' => $this->admin->id,
            'status' => 1, // Active level
            'start_date' => now()->subHours(1),
            'duration' => 120, // 2 hours
            'questions_number' => 5,
        ]);
        
    }

    protected function tearDown(): void
    {
        putenv(LaravelLocalization::ENV_ROUTE_KEY);
        parent::tearDown();
    }


    
    // ========================================
    // AUTHENTICATION & AUTHORIZATION TESTS
    // ========================================

    /**
     * Test unauthenticated users are redirected to login
     */
    /*public function test_unauthenticated_and_guest_users_are_redirected_to_login()
    {
        $response = $this->get(route('user.competitions'));
        $response->assertRedirect(route('login'));
    }*/

    /**
     * Test guest users are redirected to profile page
     */
    /* public function test_guest_users_are_redirected_to_profile_page()
    {
        $this->actingAs($this->guest_user, 'web');
        $response = $this->get(route('user.competitions'));
        $response->assertRedirect(route('user.profile.edit'));
    } */


    /**
     * Test authenticated users can access competition routes
     */
    /* public function test_authenticated_users_can_access_competition_routes()
    {
        $this->actingAs($this->user, 'web');
    
        $response = $this->get(route('user.competitions'));
        $response->assertStatus(200);
    } */
    

    /**
     * Test users can only access their own competitions
     */
    /* public function test_users_can_only_access_their_own_competitions()
    {
        $this->actingAs($this->user);
        $response = $this->get(route('user.competitions'));
        $response->assertStatus(200);
        $response->assertViewHas('competitions', function ($collection) {
            return $collection->isEmpty();
        });
        
        // Add user to competition
        $this->competition->users()->attach($this->user->id);
        
        $response = $this->get(route('user.competitions'));
        $response->assertStatus(200);
        $response->assertViewHas('competitions', function ($collection) {
            return $collection->contains($this->competition);
        });
        // Should not see competitions they're not part of
    } */

    // ========================================
    // ROUTE TESTING
    // ========================================

    /**
     * Test all competition routes exist and return correct responses
     */
    /* public function test_all_competition_routes_exist()
    {
        $this->actingAs($this->user);
        
        // Test public competitions route
        $response = $this->get(route('competitions'));
        $response->assertStatus(200);
        
        // Test user competitions route
        $response = $this->get(route('user.competitions'));
        $response->assertStatus(200);
        
        // Test competition detail route
        $response = $this->get(route('competitions.detail', $this->competition));
        $response->assertStatus(200);
        
        // Test level detail route
        $response = $this->get(route('competitions.level', $this->level));
        $response->assertStatus(200);
    } */

    /**
     * Test route model binding works correctly
     */
    /* public function test_route_model_binding_works_correctly()
    {
        $this->actingAs($this->user);
        
        // Test with valid competition
        $response = $this->get(route('competitions.detail', $this->competition));
        $response->assertStatus(200);
        
        // Test with invalid competition
        $response = $this->get(route('competitions.detail', 99999));
        $response->assertStatus(404);
        
        // Test with valid level
        $response = $this->get(route('competitions.level', $this->level));
        $response->assertStatus(200);
        
        // Test with invalid level
        $response = $this->get(route('competitions.level', 99999));
        $response->assertStatus(404);
    } */

    // ========================================
    // getAllPublicCompetitions TESTS
    // ========================================

    /**
     * Test getAllPublicCompetitions returns correct view with data
     */
    /* public function test_get_all_public_competitions_returns_correct_view()
    {        
        $response = $this->get(route('competitions'));
        
        $response->assertStatus(200);
        $response->assertViewIs('pages.user.competitions');
        $response->assertViewHas('competitions');
    } */

    /**
     * Test getAllPublicCompetitions with filters
     */
    /* public function test_get_all_public_competitions_with_filters()
    {
        $competition = Competition::factory()->create([
            'title' => 'Test',
            'status' => 1,
            'age_start' => 10,
            'age_end' => 25,
        ]);
        
        $filters = [
            'title' => 'Test',
            'status' => 1,
            'age_start' => 10,
            'age_end' => 25,
        ];
        
        $response = $this->post(route('competitions.filtred'), $filters);
        
        $response->assertStatus(200);
        $response->assertViewIs('pages.user.competitions');
        $response->assertViewHas('competitions', function ($collection) use ($competition) {
            return $collection->contains($competition) && $collection->count() == 1;
        });
    } */

    /**
     * Test getAllPublicCompetitions with invalid filters
     */
    /* public function test_get_all_public_competitions_with_invalid_filters()
    {        
        $invalidFilters = [
            'title' => '5', // Too short
            'age_end' => 5, // Too young
            'age_start' => 30,
            'status' => 3, // Invalid status
            'start_date_from' => '2025-01-1l',
            'start_date_to' => '01-02-2010',
        ];
        
        $response = $this->post(route('competitions.filtred'), $invalidFilters);

        $response->assertSessionHasErrors([
            'title',
            'age_end',
            'status',
            'start_date_from',
            'start_date_to',
        ], errorBag: 'filterCompetitions');
    } */


    // ========================================
    // competitionDetail TESTS
    // ========================================

    /**
     * Test competitionDetail returns correct view with data
     */
    /* public function test_competition_detail_returns_correct_view()
    {
        
        $response = $this->get(route('competitions.detail', $this->competition));
        
        $response->assertStatus(200);
        $response->assertViewIs('pages.user.competition_detail');
        $response->assertViewHasAll(['competition', 'users', 'audit_finish']);
    } */

    /**
     * Test competitionDetail with non-existent competition
     */
    /* public function test_competition_detail_with_non_existent_competition()
    {
        
        $response = $this->get(route('competitions.detail', 99999));
        $response->assertStatus(404);
    } */

    // ========================================
    // levelDetail TESTS
    // ========================================

    /**
     * Test levelDetail returns correct view with data
     */
    /* public function test_level_detail_returns_correct_view()
    {
        
        $response = $this->get(route('competitions.level', $this->level));
        
        $response->assertStatus(200);
        $response->assertViewIs('pages.user.level_detail');
        $response->assertViewHasAll(['level', 'users', 'audit_finish']);
    } */

    /**
     * Test levelDetail with non-existent level
     */
    /* public function test_level_detail_with_non_existent_level()
    {
        $this->actingAs($this->user);
        
        $response = $this->get(route('competitions.level', 99999));
        $response->assertStatus(404);
    } */


    // ========================================
    // levelStart TESTS
    // ========================================

    /**
     * Test levelStart with successful start
     */
    /* public function test_level_start_successful()
    {
        $this->actingAs($this->user);
        
        // Add user to competition
        $this->competition->users()->attach($this->user->id);

        // Create questions for the level
        $questions = Question::factory()->count(5)->create(['level_id' => $this->level->id]);
        
        $response = $this->get(route('user.competitions.level.response', $this->level));
        
        $response->assertStatus(200);
        $response->assertViewIs('pages.user.question_response');
        $response->assertViewHasAll(['question', 'question_count', 'level']);
        
        // Check that session has start_time
        $this->assertNotNull(session('start_time'));
    } */

    /**
     * Test levelStart when level is already completed
     */
    /* public function test_level_start_when_all_questions_answered()
    {
        $this->actingAs($this->user);
        
        // Add user to competition
        $this->competition->users()->attach($this->user->id);
        
        // Create responses for all questions
        $questions = Question::factory()->count(5)->create(['level_id' => $this->level->id]);
        foreach ($questions as $question) {
            Response::factory()->create([
                'question_id' => $question->id,
                'user_id' => $this->user->id,
            ]);
        }
        
        $response = $this->get(route('user.competitions.level.response', $this->level));
        
        $response->assertRedirect(route('user.competitions.response', ['level' => $this->level]));
    } */

    /**
     * Test levelStart when level is not available
     */
    /*  public function test_level_start_when_level_not_available()
    {
        $this->actingAs($this->user);
        
        // Make level inactive
        $this->level->update(['status' => '0']);

        $response = $this->get(route('user.competitions.level.response', $this->level));
        
        $response->assertRedirect(route('competitions.level', ['level' => $this->level]));
    } */

    /**
     * Test levelStart when user is not part of competition
     */
    /* public function test_level_start_when_user_not_in_competition()
    {
        $this->actingAs($this->user);
        
        $response = $this->get(route('user.competitions.level.response', $this->level));
        
        $response->assertRedirect(route('competitions.level', ['level' => $this->level]));
    } */

    // ========================================
    // storeResponse TESTS
    // ========================================

    /**
     * Test storeResponse with valid data
     */
    /*public function test_store_response_with_valid_data()
    {
        $this->actingAs($this->user);
        
        // Add user to competition
        $this->competition->users()->attach($this->user->id);

        // Create questions for the level
        $questions = Question::factory()->count(5)->create(['level_id' => $this->level->id]);

        // Create a response for the question
        $response = Response::factory()->create([
            'question_id' => $questions->first()->id,
            'user_id' => $this->user->id,
        ]);
        
        // Set start time in session
        session(['start_time' => now()->subMinutes(5)]);
        
        $responseData = [
            'response_text' => 'This is my answer to the question.',
            'keystrokes' => 45,
        ];
        
        $response = $this->post(route('user.competitions.level.response.store', $questions->first()), $responseData);
        
        $response->assertRedirect(route('user.competitions.level.response', ['level' => $this->level]));
        
        // Check database for response
        $this->assertDatabaseHas('responses', [
            'question_id' => $questions->first()->id,
            'user_id' => $this->user->id,
            'response_text' => $responseData['response_text'],
            'keystrokes' => $responseData['keystrokes'],
        ]);
        
        // Check that start_time is removed from session
        $this->assertNull(session('start_time'));
    }*/

    /**
     * Test storeResponse with missing start time
     */
    /*public function test_store_response_with_missing_start_time()
    {
        $this->actingAs($this->user);
        
        $responseData = [
            'response_text' => 'This is my answer.',
            'keystrokes' => 30,
        ];
        $question = Question::factory()->create(['level_id' => $this->level->id]);
        
        $response = $this->post(route('user.competitions.level.response.store', $question), $responseData);
        
        $response->assertRedirectBack();
    }*/

    /**
     * Test storeResponse with non-existent question
     */
    /*public function test_store_response_with_non_existent_question()
    {
        $this->actingAs($this->user);
        
        $responseData = [
            'response_text' => 'This is my answer.',
            'keystrokes' => 30,
        ];
        
        $response = $this->post(route('user.competitions.level.response.store', 99999), $responseData);
        
        $response->assertStatus(404);
    }*/

    /**
     * Test storeResponse calculates response duration correctly
     */
    /* public function test_store_response_calculates_duration_correctly()
    {
        $this->actingAs($this->user);
        
        // Add user to competition
        $this->competition->users()->attach($this->user->id);

        $question = Question::factory(5)->create(['level_id' => $this->level->id]);

        $response = Response::factory()->create([
            'question_id' => $question->first()->id,
            'user_id' => $this->user->id,
        ]);
        
        $startTime = now()->subMinutes(2);
        session(['start_time' => $startTime]);
        
        $responseData = [
            'response_text' => 'This is my answer.',
            'keystrokes' => 30,
        ];
        
        $response = $this->post(route('user.competitions.level.response.store', ['question' => $question->first()]), $responseData);
        
        $response->assertRedirect(route('user.competitions.level.response', ['level' => $this->level]));
        
        // Check that response duration is calculated
        $this->assertDatabaseHas('responses', [
            'question_id' => $question->first()->id,
            'user_id' => $this->user->id,
        ]);
        
        $storedResponse = Response::where('question_id', $question->first()->id)
            ->where('user_id', $this->user->id)
            ->first();
        
        $this->assertNotNull($storedResponse->response_duration);
        $this->assertGreaterThan(0, $storedResponse->response_duration);
    } */

    /**
     * Test storeResponse calculates response duration correctly
     */
    /* public function test_store_response_penalty_detection_correctly()
    {
        $this->actingAs($this->user);
        
        // Add user to competition
        $this->competition->users()->attach($this->user->id);

        $question = Question::factory()->create(['level_id' => $this->level->id]);

        $response = Response::factory()->create([
            'question_id' => $question->id,
            'user_id' => $this->user->id,
        ]);
        
        $startTime = now()->subSeconds(5);
        session(['start_time' => $startTime]);
        
        $responseData = [
            'response_text' => str_repeat('word ', 20),
            'keystrokes' => 2,
        ];

        session(['tab_switched' => true]);
        
        $response = $this->post(route('user.competitions.level.response.store', ['question' => $question->first()]), $responseData);
        
        $response->assertRedirect(route('user.competitions.level.response', ['level' => $this->level]));
        
        // Check that response duration is calculated
        $this->assertDatabaseHas('responses', [
            'question_id' => $question->first()->id,
            'user_id' => $this->user->id,
        ]);

        $storedResponse = Response::where('question_id', $question->first()->id)
            ->where('user_id', $this->user->id)
            ->first();
        
        $this->assertNotNull($storedResponse->response_duration);
        $this->assertGreaterThan(0, $storedResponse->response_duration);
        $this->assertEquals(
            ['too_fast_long_answer','low_keystrokes','suspicious_wpm','tab_switch'],
            json_decode($storedResponse->flags, true)
        );
    } */

    // ========================================
    // userResponses TESTS
    // ========================================

    /**
     * Test userResponses returns correct view with data
     */
/*     public function test_user_responses_returns_correct_view()
    {
        $this->actingAs($this->user);
        
        // Add user to competition
        $this->competition->users()->attach($this->user->id);
        
        // Create some responses
        $questions = Question::factory()->count(3)->create(['level_id' => $this->level->id]);
        foreach ($questions as $question) {
            Response::factory()->create([
                'question_id' => $question->id,
                'user_id' => $this->user->id,
            ]);
        }
        
        $response = $this->get(route('user.competitions.response', $this->level));
        
        $response->assertStatus(200);
        $response->assertViewIs('pages.user.user_responses_list');
        $response->assertViewHasAll(['level', 'responses']);
    } */

    /**
     * Test userResponses returns empty when no responses
     */
/*     public function test_user_responses_returns_empty_when_none()
    {
        $this->actingAs($this->user);
        
        // Add user to competition
        $this->competition->users()->attach($this->user->id);
        
        $response = $this->get(route('user.competitions.response', $this->level));
        
        $response->assertStatus(200);
        $response->assertViewIs('pages.user.user_responses_list');
        $response->assertViewHas('responses');
        $this->assertEquals(0, $response->viewData('responses')->total());
    } */

    /**
     * Test userResponses only shows user's own responses
     */
/*     public function test_user_responses_only_shows_user_own_responses()
    {
        $this->actingAs($this->user);
        
        // Add user to competition
        $this->competition->users()->attach($this->user->id);
        
        $otherUser = User::factory()->create();
        
        // Create responses for both users
        $questions = Question::factory()->count(2)->create(['level_id' => $this->level->id]);
        foreach ($questions as $question) {
            Response::factory()->create([
                'question_id' => $question->id,
                'user_id' => $this->user->id,
            ]);
            Response::factory()->create([
                'question_id' => $question->id,
                'user_id' => $otherUser->id,
            ]);
        }
        
        $response = $this->get(route('user.competitions.response', $this->level));
        
        $response->assertStatus(200);
        $responses = $response->viewData('responses');
        $this->assertEquals(2, $responses->total());
        
        foreach ($responses as $response) {
            $this->assertEquals($this->user->id, $response->user_id);
        }
    } */

    // ========================================
    // competitorsLevelOrder TESTS
    // ========================================

    /**
     * Test competitorsLevelOrder returns correct view with data
     */
    public function test_competitors_level_order_returns_correct_view()
    {
        $this->actingAs($this->user);
        
        $response = $this->get(route('competitions.level.order', $this->level));
        
        $response->assertStatus(200);
        $response->assertViewIs('pages.user.competitors_level_order');
        $response->assertViewHasAll(['level', 'users', 'audit_finish']);
    }

    /**
     * Test competitorsLevelOrder with different level statuses
     */
    public function test_competitors_level_order_with_different_statuses()
    {
        $this->actingAs($this->user);
        
        // Test with active level
        $response = $this->get(route('competitions.level.order', $this->level));
        $response->assertStatus(200);
        
        // Test with finished level
        $this->level->update(['status' => 2]);
        $response = $this->get(route('competitions.level.order', $this->level));
        $response->assertStatus(200);
    }

    // ========================================
    // competitorsCompetitionOrder TESTS
    // ========================================

    /**
     * Test competitorsCompetitionOrder returns correct view with data
     */
    public function test_competitors_competition_order_returns_correct_view()
    {
        $this->actingAs($this->user);
        
        $response = $this->get(route('competitions.order', $this->competition));
        
        $response->assertStatus(200);
        $response->assertViewIs('pages.user.competitors_competition_order');
        $response->assertViewHasAll(['competition', 'users', 'audit_finish']);
    }

    /**
     * Test competitorsCompetitionOrder with different competition statuses
     */
    public function test_competitors_competition_order_with_different_statuses()
    {
        $this->actingAs($this->user);
        
        // Test with active competition
        $response = $this->get(route('competitions.order', $this->competition));
        $response->assertStatus(200);
        
        // Test with finished competition
        $this->competition->update(['status' => 2]);
        $response = $this->get(route('competitions.order', $this->competition));
        $response->assertStatus(200);
    }


    // ========================================
    // INTEGRATION TESTS
    // ========================================

    /**
     * Test complete competition flow
     */
    public function test_complete_competition_flow()
    {
        $this->actingAs($this->user);
        
        // Add user to competition
        $this->competition->users()->attach($this->user->id);
        
        // Create multiple questions
        $questions = Question::factory()->count(3)->create(['level_id' => $this->level->id]);
        
        // Complete all questions
        foreach ($questions as $index => $question) {
            // Start level
            $response = $this->get(route('user.competitions.level.response', $this->level));
            $response->assertStatus(200);
            
            // Submit response
            session(['start_time' => now()->subMinutes(1)]);
            $responseData = [
                'response_text' => "Answer to question " . ($index + 1),
                'keystrokes' => rand(20, 50),
            ];
            
            $this->post(route('user.competitions.level.response.store', $question), $responseData);
        }
        
        // Try to start level again (should redirect to responses)
        $response = $this->get(route('user.competitions.level.response', $this->level));
        $response->assertRedirect(route('user.competitions.response', ['level' => $this->level]));
        
        // Check user responses
        $response = $this->get(route('user.competitions.response', $this->level));
        $response->assertStatus(200);
        $this->assertEquals(3, $response->viewData('responses')->total());
    }

    /**
     * Test competition with multiple users
     */
    public function test_competition_with_multiple_users()
    {
        $user1 = $this->user;
        $user2 = User::factory()->create();
        
        // Add both users to competition
        $this->competition->users()->attach([$user1->id, $user2->id]);
        
        // Create questions
        $questions = Question::factory()->count(2)->create(['level_id' => $this->level->id]);
        
        // User 1 responds to questions
        $this->actingAs($user1);
        foreach ($questions as $question) {
            $response = $this->get(route('user.competitions.level.response', $this->level));
            $response->assertStatus(200);
            session(['start_time' => now()->subMinutes(1)]);
            $this->post(route('user.competitions.level.response.store', $question), [
                'response_text' => "User 1 answer",
                'keystrokes' => 30,
            ]);
        }
        
        // User 2 responds to questions
        $this->actingAs($user2);
        foreach ($questions as $question) {
            $response = $this->get(route('user.competitions.level.response', $this->level));
            $response->assertStatus(200);
            session(['start_time' => now()->subMinutes(1)]);
            $this->post(route('user.competitions.level.response.store', $question), [
                'response_text' => "User 2 answer",
                'keystrokes' => 35,
            ]);
        }
        
        // Check total responses
        $this->assertEquals(4, Response::count());
        $this->assertEquals(2, Response::where('user_id', $user1->id)->count());
        $this->assertEquals(2, Response::where('user_id', $user2->id)->count());
    }
} 