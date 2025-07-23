<?php

namespace Tests\Feature\Controllers\Competition;

use App\Models\Admin\Admin;
use App\Models\Competition\Competition;
use App\Models\Competition\Level;
use App\Models\Competition\Question;
use App\Models\Competition\Response;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuditControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $auditor;
    protected Admin $nonAuditor;
    protected Competition $competition;
    protected Competition $otherCompetition;
    protected Level $level;
    protected Level $otherLevel;
    protected User $user;
    protected Question $question;
    protected Response $response;

    protected function setUp(): void
    {
        parent::setUp();
        $this->refreshApplicationWithLocale('en');
        // Arrange: Create main test data
        $this->auditor = Admin::factory()->create();
        $this->nonAuditor = Admin::factory()->create();
        $this->competition = Competition::factory()->create();
        $this->otherCompetition = Competition::factory()->create();
        $this->competition->auditors()->attach($this->auditor);
        $this->level = Level::factory()->for($this->competition)->create();
        $this->otherLevel = Level::factory()->for($this->otherCompetition)->create();
        $this->user = User::factory()->create();
        $this->question = Question::factory()->for($this->level)->create();
        $this->response = Response::factory()->for($this->question)->for($this->user)->create(['admin_id' => null, 'score' => 0]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    // ========================================
    // AUTHENTICATION & AUTHORIZATION TESTS
    // ========================================

    /**
     * Test all audit routes require admin authentication.
     */
    public function test_all_routes_require_authentication()
    {
        // Act & Assert
        $routes = [
            'get' => [
                route('admin.auditor.competitions', [], false),
                route('admin.auditor.users', ['level' => $this->level], false),
                route('admin.auditor.users.responses', ['level' => $this->level, 'user_id' => $this->user->anonymized_identifier], false),
            ],
            'post' => [
                route('admin.auditor.competition.filtred', [], false),
                route('admin.auditor.users.responses.audit_score', ['level' => $this->level, 'user' => $this->user], false),
            ],
        ];
        foreach ($routes['get'] as $uri) {
            $this->get($uri)->assertRedirect(route('login'));
        }
        foreach ($routes['post'] as $uri) {
            $this->post($uri, [])->assertRedirect(route('login'));
        }
    }

    // ========================================
    // ROUTE PARAMETER VALIDATION
    // ========================================

    /**
     * Test route model binding for Level and User models.
     */
    public function test_route_model_binding_for_level_and_user()
    {
        // Act & Assert
        $this->actingAs($this->auditor, 'admin')
            ->get(route('admin.auditor.users', ['level' => $this->level->id]))
            ->assertOk();
        $this->actingAs($this->auditor, 'admin')
            ->get(route('admin.auditor.users.responses', ['level' => $this->level->id, 'user_id' => $this->user->anonymized_identifier]))
            ->assertOk();
    }

    /**
     * Test 404 responses for non-existent resources.
     */
    public function test_404_for_non_existent_resources()
    {
        // Act & Assert
        $this->actingAs($this->auditor, 'admin')
            ->get(route('admin.auditor.users', ['level' => 99999]))
            ->assertStatus(404);
    }

    // ========================================
    // REQUEST VALIDATION TESTS
    // ========================================

    /**
     * Test FilterCompetitionRequest validation rules and error messages.
     */
    public function test_filter_competition_request_validation()
    {
        // Act
        $response = $this->actingAs($this->auditor, 'admin')
            ->post(route('admin.auditor.competition.filtred'), [
                'title' => 'k',
                'start_date_from' => 'invalid-date',
                'age_start' => 'not-an-integer',
                'status' => 'invalid',
            ]);
            // Assert
            $response->assertSessionHasErrors([
                'title',
                'status',
                'start_date_from',
                'age_start'
            ], errorBag: 'filterCompetitions');
    }

    /**
     * Test AuditUserResponsesScoreRequest validation rules and error messages.
     */
    public function test_audit_user_responses_score_request_validation()
    {
        // Act
        $response = $this->actingAs($this->auditor, 'admin')
            ->post(route('admin.auditor.users.responses.audit_score', ['level' => $this->level->id, 'user' => $this->user->id]), [
                'scores' => 'not-an-array',
            ]);
        // Assert
        $response->assertSessionHasErrors(['scores'],errorBag: 'auditUserResponses');
    }

    /**
     * Test required fields, data types, and custom validation rules.
     */
    public function test_audit_user_responses_score_request_required_fields()
    {
        // Act
        $this->actingAs($this->auditor, 'admin')
            ->post(route('admin.auditor.users.responses.audit_score', ['level' => $this->level->id, 'user' => $this->user->id]), [])
            // Assert
            ->assertSessionHasErrors(['scores'],errorBag: 'auditUserResponses');
    }

    // ========================================
    // FUNCTIONALITY TESTS
    // ========================================

    /**
     * Test auditCompetitions view renders with correct data.
     */
    public function test_audit_competitions_view_renders_with_data()
    {
        // Act
        $response = $this->actingAs($this->auditor, 'admin')
            ->get(route('admin.auditor.competitions'));
        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.admins.auditor.audited_competitions');
        $response->assertViewHas('competitions');
    }

    /**
     * Test auditUsers view renders with correct data.
     */
    public function test_audit_users_view_renders_with_data()
    {
        // Act
        $response = $this->actingAs($this->auditor, 'admin')
            ->get(route('admin.auditor.users', ['level' => $this->level->id]));
        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.admins.auditor.audited_users');
        $response->assertViewHasAll(['level', 'admin_id']);
    }

    /**
     * Test auditUserResponses view renders with correct data.
     */
    public function test_audit_user_responses_view_renders_with_data()
    {
        // Act
        $response = $this->actingAs($this->auditor, 'admin')
            ->get(route('admin.auditor.users.responses', ['level' => $this->level->id, 'user_id' => $this->user->anonymized_identifier]));
        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.admins.auditor.audited_user_responses_submit');
        $response->assertViewHasAll(['level', 'user', 'questions']);
    }

    /**
     * Test submitAudit updates response scores and flashes success.
     */
    public function test_submit_audit_updates_scores_and_flashes_success()
    {
        // Arrange
        \DB::table('level_admin_user')->insert([
            'level_id' => $this->level->id,
            'user_id' => $this->user->id,
            'admin_id' => $this->auditor->id,
        ]);

        // Act
        $response = $this->actingAs($this->auditor, 'admin')
            ->post(route('admin.auditor.users.responses.audit_score', ['level' => $this->level->id, 'user' => $this->user->id]), [
                'scores' => [ $this->response->id => 10 ],
            ]);
        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas('responses', [
            'id' => $this->response->id,
            'score' => 10,
        ]);
    }

    // ========================================
    // INTEGRATION TESTS
    // ========================================

    /**
     * Test the complete audit workflow from competition selection to score submission.
     */
    public function test_complete_audit_workflow()
    {
        // Arrange
        \DB::table('level_admin_user')->insert([
            'level_id' => $this->level->id,
            'user_id' => $this->user->id,
            'admin_id' => $this->auditor->id,
        ]);
        // 1. Auditor sees competitions
        $this->actingAs($this->auditor, 'admin')
            ->get(route('admin.auditor.competitions'))
            ->assertOk();
        // 2. Auditor sees users for a level
        $this->actingAs($this->auditor, 'admin')
            ->get(route('admin.auditor.users', ['level' => $this->level->id]))
            ->assertOk();
        // 3. Auditor sees user responses
        $this->actingAs($this->auditor, 'admin')
            ->get(route('admin.auditor.users.responses', ['level' => $this->level->id, 'user_id' => $this->user->anonymized_identifier]))
            ->assertOk();
        // 4. Auditor submits audit
        $this->actingAs($this->auditor, 'admin')
            ->post(route('admin.auditor.users.responses.audit_score', ['level' => $this->level->id, 'user' => $this->user->id]), [
                'scores' => [ $this->response->id => 5 ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('responses', [
            'id' => $this->response->id,
            'score' => 5,
        ]);
    }

    // ========================================
    // EDGE CASES & ERROR HANDLING
    // ========================================

    /**
     * Test auditUserResponses returns 404 for missing user.
     */
    public function test_audit_user_responses_404_for_missing_user()
    {
        // Act & Assert
        $missingIdentifier = Str::uuid();
        $this->actingAs($this->auditor, 'admin')
            ->get(route('admin.auditor.users.responses', ['level' => $this->level->id, 'user_id' => $missingIdentifier]))
            ->assertStatus(404);
    }

    /**
     * Test submitAudit handles service exceptions and does not update DB.
     */
    public function test_submit_audit_handles_service_exceptions()
    {
        // No pivot entry, so permission fails
        $response = $this->actingAs($this->auditor, 'admin')
            ->post(route('admin.auditor.users.responses.audit_score', ['level' => $this->level->id, 'user' => $this->user->id]), [
                'scores' => [ $this->response->id => 10 ],
            ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('responses', [
            'id' => $this->response->id,
            'score' => 0,
            'admin_id' => null,
        ]);
    }

    /**
     * Test transaction rollbacks on failures (simulate by invalid response ID).
     */
    public function test_transaction_rollback_on_failure()
    {
        // Arrange
        \DB::table('level_admin_user')->insert([
            'level_id' => $this->level->id,
            'user_id' => $this->user->id,
            'admin_id' => $this->auditor->id,
        ]);
        $invalidId = 999999;
        // Act
        $response = $this->actingAs($this->auditor, 'admin')
            ->post(route('admin.auditor.users.responses.audit_score', ['level' => $this->level->id, 'user' => $this->user->id]), [
                'scores' => [ $invalidId => 10 ],
            ]);
        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas('responses', [
            'id' => $this->response->id,
            'score' => 0,
            'admin_id' => null,
        ]);
    }

    /**
     * Test concurrent audit attempts (submitting twice quickly).
     */
    public function test_concurrent_audit_attempts()
    {
        // Arrange
        \DB::table('level_admin_user')->insert([
            'level_id' => $this->level->id,
            'user_id' => $this->user->id,
            'admin_id' => $this->auditor->id,
        ]);
        // First attempt
        $this->actingAs($this->auditor, 'admin')
            ->post(route('admin.auditor.users.responses.audit_score', ['level' => $this->level->id, 'user' => $this->user->id]), [
                'scores' => [ $this->response->id => 5 ],
            ]);
        // Second attempt
        $this->actingAs($this->auditor, 'admin')
            ->post(route('admin.auditor.users.responses.audit_score', ['level' => $this->level->id, 'user' => $this->user->id]), [
                'scores' => [ $this->response->id => 10 ],
            ]);
        $this->assertDatabaseHas('responses', [
            'id' => $this->response->id,
            'score' => 5,
        ]);
    }
} 