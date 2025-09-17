<?php

namespace Tests\Feature\Controllers\GuestUsers;

use Tests\TestCase;
use App\Models\Admin\Admin;
use App\Models\GuestUsers\GlobalQuestion;
use App\Models\GuestUsers\Choice;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;

class GlobalQuestionControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Admin $owner;
    protected Admin $superAdmin;
    protected Admin $manager;
    protected Admin $regularAdmin;
    protected GlobalQuestion $approvedQuestion;
    protected GlobalQuestion $unapprovedQuestion;
    protected GlobalQuestion $managerQuestion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->refreshApplicationWithLocale('en');

        // Seed roles
        $this->seed(RoleSeeder::class);

        // Create admins with different roles
        $this->owner = Admin::factory()->create(['name' => 'Owner']);
        $this->owner->assignRole('owner');

        $this->superAdmin = Admin::factory()->create(['name' => 'Super Admin']);
        $this->superAdmin->assignRole('super_admin');

        $this->manager = Admin::factory()->create(['name' => 'Manager']);
        $this->manager->assignRole('manager');

        $this->regularAdmin = Admin::factory()->create(['name' => 'Regular Admin']);
        $this->regularAdmin->assignRole('manager');

        // Create test questions
        $this->approvedQuestion = GlobalQuestion::factory()->create([
            'admin_id' => $this->superAdmin->id,
            'approved' => $this->owner->id,
            'question_text' => 'Test approved question?',
            'score' => 10,
            'duration' => 60,
            'text_direction' => 'ltr',
        ]);

        $this->unapprovedQuestion = GlobalQuestion::factory()->create([
            'admin_id' => $this->regularAdmin->id,
            'approved' => null,
            'question_text' => 'Test unapproved question?',
            'score' => 15,
            'duration' => 90,
            'text_direction' => 'rtl',
        ]);

        $this->managerQuestion = GlobalQuestion::factory()->create([
            'admin_id' => $this->manager->id,
            'approved' => null,
            'question_text' => 'Manager question?',
            'score' => 5,
            'duration' => 45,
            'text_direction' => 'ltr',
        ]);

        // Create choices for questions
        $this->createChoicesForQuestion($this->approvedQuestion);
        $this->createChoicesForQuestion($this->unapprovedQuestion);
        $this->createChoicesForQuestion($this->managerQuestion);
    }

    // ========================================
    // AUTHENTICATION & AUTHORIZATION TESTS
    // ========================================

    public function test_unauthenticated_users_are_redirected_to_login()
    {
        $routes = [
            ['get', route('admin.global_questions')],
            ['post', route('admin.global_questions.store')],
            ['delete', route('admin.global_question.delete')],
            ['put', route('admin.global_question.approve')],
        ];

        foreach ($routes as [$method, $uri]) {
            $response = $this->{$method}($uri);
            $response->assertRedirect(route('login'));
        }
    }

    public function test_authenticated_admins_can_access_global_questions_list()
    {
        $this->actingAs($this->superAdmin, 'admin');
        
        $response = $this->get(route('admin.global_questions'));
        $response->assertStatus(200);
        $response->assertViewIs('pages.admin.guest_users.global_question_list');
    }

    public function test_any_authenticated_admin_can_create_questions()
    {
        $this->actingAs($this->regularAdmin, 'admin');
        
        $data = $this->getValidQuestionData();
        
        $response = $this->post(route('admin.global_questions.store'), $data);
        $response->assertRedirectBack();
        
        $this->assertDatabaseHas('global_questions', [
            'question_text' => $data['question_text'],
            'admin_id' => $this->regularAdmin->id,
            'approved' => null, // Regular admin questions are not auto-approved
        ]);
    }

    public function test_only_owner_can_approve_questions()
    {
        // Owner can approve
        $this->actingAs($this->owner, 'admin');
        $response = $this->put(route('admin.global_question.approve'), ['id' => $this->unapprovedQuestion->id]);
        $response->assertRedirectBack();
        
        $this->assertDatabaseHas('global_questions', [
            'id' => $this->unapprovedQuestion->id,
            'approved' => $this->owner->id,
        ]);
        
    }

    public function test_only_super_admin_can_approve_questions()
    {
        // Super admin can approve
        $this->actingAs($this->superAdmin, 'admin');
        $response = $this->put(route('admin.global_question.approve'), ['id' => $this->managerQuestion->id]);
        $response->assertRedirectBack();
        
        $this->assertDatabaseHas('global_questions', [
            'id' => $this->managerQuestion->id,
            'approved' => $this->superAdmin->id,
        ]);
    }

    public function test_manager_admin_cannot_approve_questions()
    {

        // Manager cannot approve
        $this->actingAs($this->manager, 'admin');
        $response = $this->put(route('admin.global_question.approve'), ['id' => $this->unapprovedQuestion->id]);
        $response->assertForbidden();
        
        $this->assertDatabaseMissing('global_questions', [
            'id' => $this->unapprovedQuestion->id,
            'approved' => $this->manager->id,
        ]);
    }

    public function test_questions_can_be_deleted_by_creator_or_authorized_admins()
    {
        // Creator can delete their own question
        $this->actingAs($this->regularAdmin, 'admin');
        $response = $this->delete(route('admin.global_question.delete'), ['id' => $this->unapprovedQuestion->id]);
        $response->assertRedirectBack();
        
        $this->assertDatabaseMissing('global_questions', [
            'id' => $this->unapprovedQuestion->id,
        ]);

        // Super admin can delete any question
        $this->actingAs($this->superAdmin, 'admin');
        $response = $this->delete(route('admin.global_question.delete'), ['id' => $this->managerQuestion->id]);
        $response->assertRedirectBack();
        
        $this->assertDatabaseMissing('global_questions', [
            'id' => $this->managerQuestion->id,
        ]);
    }

    // ========================================
    // STORE METHOD TESTS
    // ========================================

    public function test_successful_question_creation()
    {
        $this->actingAs($this->superAdmin, 'admin');
        
        $data = $this->getValidQuestionData();
        
        $response = $this->post(route('admin.global_questions.store'), $data);
        $response->assertRedirectBack();
        
        $this->assertDatabaseHas('global_questions', [
            'question_text' => $data['question_text'],
            'score' => $data['score'],
            'duration' => $data['duration'],
            'text_direction' => $data['txt_direction'],
            'admin_id' => $this->superAdmin->id,
            'approved' => $this->superAdmin->id, // Super admin questions are auto-approved
        ]);

        // Check that choices were created
        $question = GlobalQuestion::with('choices')->where('question_text', $data['question_text'])->first();
        $this->assertEquals(count($data['choice']), $question->choices()->count());
        $this->assertDatabaseHas('choices', [
            'question_id' => $question->id,
            'choice_text' => $data['choice'][0],
            'correct' => true, // First choice should be correct
        ]);
    }

    public function test_question_creation_with_validation_errors()
    {
        $this->actingAs($this->regularAdmin, 'admin');
        
        $invalidData = [
            'question_text' => '', // Required
            'duration' => 'invalid', // Must be integer
            'score' => -1, // Must be positive
            'choice' => ['only_one_choice'], // Must have at least 2 choices
            'txt_direction' => 'invalid', // Must be ltr or rtl
        ];

        $response = $this->post(route('admin.global_questions.store'), $invalidData);
        $response->assertSessionHasErrors([
            'question_text',
            'duration',
            'score',
            'choice',
            'txt_direction',
        ], errorBag:'createQuestion');
    }

    public function test_question_creation_with_too_many_choices()
    {
        $this->actingAs($this->regularAdmin, 'admin');
        
        $data = $this->getValidQuestionData();
        $data['choice'] = ['Choice 1', 'Choice 2', 'Choice 3', 'Choice 4', 'Choice 5', 'Choice 6']; // More than 5

        $response = $this->post(route('admin.global_questions.store'), $data);
        $response->assertSessionHasErrors(['choice'], errorBag:'createQuestion');
    }

    public function test_question_creation_with_too_few_choices()
    {
        $this->actingAs($this->regularAdmin, 'admin');
        
        $data = $this->getValidQuestionData();
        $data['choice'] = ['Only one choice']; // Less than 2

        $response = $this->post(route('admin.global_questions.store'), $data);
        $response->assertSessionHasErrors(['choice'], errorBag:'createQuestion');
    }

    public function test_question_creation_with_empty_choice_text()
    {
        $this->actingAs($this->regularAdmin, 'admin');
        
        $data = $this->getValidQuestionData();
        $data['choice'] = ['Valid choice', '', 'Another valid choice'];

        $response = $this->post(route('admin.global_questions.store'), $data);
        $response->assertSessionHasErrors(['choice.1'], errorBag:'createQuestion');
    }

    public function test_question_creation_with_invalid_duration()
    {
        $this->actingAs($this->regularAdmin, 'admin');
        
        $data = $this->getValidQuestionData();
        $data['duration'] = 20; // Less than minimum 30

        $response = $this->post(route('admin.global_questions.store'), $data);
        $response->assertSessionHasErrors(['duration'], errorBag:'createQuestion');
    }

    public function test_question_creation_with_invalid_score()
    {
        $this->actingAs($this->regularAdmin, 'admin');
        
        $data = $this->getValidQuestionData();
        $data['score'] = 0; // Must be at least 1

        $response = $this->post(route('admin.global_questions.store'), $data);
        $response->assertSessionHasErrors(['score'], errorBag:'createQuestion');
    }

    // ========================================
    // APPROVE METHOD TESTS
    // ========================================

    public function test_successful_question_approval()
    {
        $this->actingAs($this->owner, 'admin');
        
        $response = $this->put(route('admin.global_question.approve'), ['id' => $this->unapprovedQuestion->id]);
        $response->assertRedirectBack();
        
        $this->assertDatabaseHas('global_questions', [
            'id' => $this->unapprovedQuestion->id,
            'approved' => $this->owner->id,
        ]);
    }

    public function test_cannot_approve_already_approved_question()
    {
        $this->actingAs($this->owner, 'admin');
        
        $response = $this->put(route('admin.global_question.approve'), ['id' => $this->approvedQuestion->id]);
        $response->assertRedirectBack();
        
        // Should not change the approval status
        $this->assertDatabaseHas('global_questions', [
            'id' => $this->approvedQuestion->id,
            'approved' => $this->owner->id, // Should remain the same
        ]);
    }

    public function test_cannot_approve_nonexistent_question()
    {
        $this->actingAs($this->owner, 'admin');
        
        $response = $this->put(route('admin.global_question.approve'), ['id' => 99999]);
        $response->assertNotFound();
    }

    public function test_approval_requires_authentication()
    {
        $response = $this->put(route('admin.global_question.approve'), ['id' => $this->unapprovedQuestion->id]);
        $response->assertRedirect(route('login'));
    }

    public function test_approval_requires_authorization()
    {
        $this->actingAs($this->manager, 'admin');
        
        $response = $this->put(route('admin.global_question.approve'), ['id' => $this->unapprovedQuestion->id]);
        $response->assertForbidden();
        
        // Should not be approved
        $this->assertDatabaseHas('global_questions', [
            'id' => $this->unapprovedQuestion->id,
            'approved' => null,
        ]);
    }

    // ========================================
    // DELETE METHOD TESTS
    // ========================================

    public function test_successful_question_deletion()
    {
        $this->actingAs($this->regularAdmin, 'admin');
        
        $response = $this->delete(route('admin.global_question.delete'), ['id' => $this->unapprovedQuestion->id]);
        $response->assertRedirectBack();
        
        $this->assertDatabaseMissing('global_questions', [
            'id' => $this->unapprovedQuestion->id,
        ]);

        // Choices should also be deleted (cascade)
        $this->assertDatabaseMissing('choices', [
            'question_id' => $this->unapprovedQuestion->id,
        ]);
    }

    public function test_cannot_delete_nonexistent_question()
    {
        $this->actingAs($this->superAdmin, 'admin');
        
        $response = $this->delete(route('admin.global_question.delete'), ['id' => 99999]);
        $response->assertNotFound();
        
        // Should not affect existing questions
        $this->assertDatabaseCount('global_questions', 3);
    }

    public function test_deletion_requires_authentication()
    {
        $response = $this->delete(route('admin.global_question.delete'), ['id' => $this->unapprovedQuestion->id]);
        $response->assertRedirect(route('login'));
    }

    public function test_deletion_requires_authorization()
    {
        // Manager trying to delete super admin's question
        $this->actingAs($this->manager, 'admin');
        
        $response = $this->delete(route('admin.global_question.delete'), ['id' => $this->approvedQuestion->id]);
        $response->assertRedirectBack();
        
        // Should not be deleted
        $this->assertDatabaseHas('global_questions', [
            'id' => $this->approvedQuestion->id,
        ]);
    }

    // ========================================
    // ALL METHOD TESTS
    // ========================================

    public function test_all_method_returns_correct_view()
    {
        $this->actingAs($this->superAdmin, 'admin');
        
        $response = $this->get(route('admin.global_questions'));
        $response->assertStatus(200);
        $response->assertViewIs('pages.admin.guest_users.global_question_list');
    }

    public function test_all_method_requires_authentication()
    {
        $response = $this->get(route('admin.global_questions'));
        $response->assertRedirect(route('login'));
    }

    // ========================================
    // EDGE CASES AND ERROR HANDLING
    // ========================================

    public function test_question_creation_with_special_characters()
    {
        $this->actingAs($this->regularAdmin, 'admin');
        
        $data = $this->getValidQuestionData();
        $data['question_text'] = 'Test question with special chars: !@#$%^&*()?';
        $data['choice'] = ['Choice with émojis 🎉', 'Another choice with symbols @#$%', 'Normal choice'];

        $response = $this->post(route('admin.global_questions.store'), $data);
        $response->assertRedirectBack();
        
        $this->assertDatabaseHas('global_questions', [
            'question_text' => $data['question_text'],
        ]);
    }

    public function test_question_creation_with_maximum_length_values()
    {
        $this->actingAs($this->regularAdmin, 'admin');
        
        $data = $this->getValidQuestionData();
        $data['question_text'] = str_repeat('a', 400); // Maximum length
        $data['choice'] = [
            str_repeat('a', 400), // Maximum length
            str_repeat('b', 400),
            str_repeat('c', 400),
        ];

        $response = $this->post(route('admin.global_questions.store'), $data);
        $response->assertRedirectBack();
        
        $this->assertDatabaseHas('global_questions', [
            'question_text' => $data['question_text'],
        ]);
    }

    public function test_question_creation_with_minimum_length_values()
    {
        $this->actingAs($this->regularAdmin, 'admin');
        
        $data = $this->getValidQuestionData();
        $data['question_text'] = 'abc'; // Minimum length
        $data['choice'] = [
            'abc', // Minimum length
            'def',
            'ghi',
        ];

        $response = $this->post(route('admin.global_questions.store'), $data);
        $response->assertRedirectBack();
        
        $this->assertDatabaseHas('global_questions', [
            'question_text' => $data['question_text'],
        ]);
    }

    public function test_question_creation_with_boundary_values()
    {
        $this->actingAs($this->regularAdmin, 'admin');
        
        $data = $this->getValidQuestionData();
        $data['duration'] = 30; // Minimum duration
        $data['score'] = 1; // Minimum score

        $response = $this->post(route('admin.global_questions.store'), $data);
        $response->assertRedirectBack();
        
        $this->assertDatabaseHas('global_questions', [
            'duration' => 30,
            'score' => 1,
        ]);
    }

    public function test_question_creation_with_exactly_five_choices()
    {
        $this->actingAs($this->regularAdmin, 'admin');
        
        $data = $this->getValidQuestionData();
        $data['choice'] = ['Choice 1', 'Choice 2', 'Choice 3', 'Choice 4', 'Choice 5'];

        $response = $this->post(route('admin.global_questions.store'), $data);
        $response->assertRedirectBack();
        
        $question = GlobalQuestion::with('choices')->where('question_text', $data['question_text'])->first();
        $this->assertEquals(5, $question->choices()->count());
    }

    public function test_question_creation_with_exactly_two_choices()
    {
        $this->actingAs($this->regularAdmin, 'admin');
        
        $data = $this->getValidQuestionData();
        $data['choice'] = ['Choice 1', 'Choice 2'];

        $response = $this->post(route('admin.global_questions.store'), $data);
        $response->assertRedirectBack();
        
        $question = GlobalQuestion::with('choices')->where('question_text', $data['question_text'])->first();
        $this->assertEquals(2, $question->choices()->count());
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    private function getValidQuestionData(): array
    {
        return [
            'question_text' => 'Test question for global users?',
            'duration' => 60,
            'explanation' => null,
            'score' => 10,
            'choice' => [
                'Correct answer',
                'Incorrect answer 1',
                'Incorrect answer 2',
                'Incorrect answer 3',
            ],
            'txt_direction' => 'ltr',
        ];
    }


    private function createChoicesForQuestion(GlobalQuestion $question): void
    {
        Choice::factory()->count(4)->create([
            'question_id' => $question->id,
            'correct' => false,
        ]);

        // Create one correct choice
        Choice::factory()->create([
            'question_id' => $question->id,
            'choice_text' => 'Correct answer',
            'correct' => true,
        ]);
    }
} 