<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Admin\Admin;
use App\Models\Competition\Level;
use App\Models\Competition\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

class QuestionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $level;
    protected $question;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->admin = Admin::factory()->create();
        
        // Create a level
        $this->level = Level::factory()->create([
            'status' => 'pending' // Inactive level
        ]);

        // Create a question
        $this->question = Question::factory()->create([
            'level_id' => $this->level->id
        ]);

        // auth admin
        $this->actingAs($this->admin, 'admin');
    }

    public function test_store_questions_success()
    {
        // Arrange
        $data = [
            'question_text' => ['New Question 1', 'New Question 2'],
            'duration' => [30, 45],
            'max_score' => [10, 15],
            'perfect_response' => ["001","002"],
        ];

        // Act
        $response = $this->post(route('admin.competitions.level.question.store', ['level' => $this->level]), $data);

        // Assert
        $response->assertRedirectBack();
        $this->assertDatabaseCount('questions', 3);// 2 questions + 1 question in the factory
    }

    public function test_store_questions_validation_failure()
    {
        // Arrange
        $data = [
            'level_id' => $this->level->id,
            'question_text' => [''],
            'duration' => ['invalid'],
            'max_score' => ['invalid']
        ];

        // Act
        $response = $this->post(route('admin.competitions.level.question.store', ['level' => $this->level]), $data);

        // Assert
        $response->assertSessionHasErrors(
            ['question_text.0', 'duration.0', 'max_score.0'],
            errorBag: 'createQuestion'
        );
        $this->assertDatabaseCount('questions', 1);// 1 question in the factory
    }

    public function test_store_questions_active_level_failure()
    {
        // Arrange
        $this->level->update(['status' => 'active']); // Make level active
        $data = [
            'level_id' => $this->level->id,
            'question_text' => ['New Question'],
            'duration' => [30],
            'max_score' => [10]
        ];

        // Act
        $response = $this->post(route('admin.competitions.level.question.store', ['level' => $this->level]), $data);

        // Assert
        $response->assertRedirectBack();
        $this->assertDatabaseMissing('questions', [
            'level_id' => $this->level->id,
            'question_text' => 'New Question'
        ]);
    }

    public function test_store_questions_max_number_failure()
    {
        // Arrange
        $data = [
            'level_id' => $this->level->id,
            'question_text' => ['New Question 1', 'New Question 2', 'New Question 3'],  
            'duration' => [30, 45, 60],
            'max_score' => [10, 15, 20]
        ];

        $this->level->update(['questions_number' => 2]);

        // Act
        $response = $this->post(route('admin.competitions.level.question.store', ['level' => $this->level]), $data);         

        // Assert
        $response->assertRedirectBack();
        $this->assertStringContainsString(
            __('messages.validation.not_allow.question_update_max_number',['number' => $this->level->questions_number]),
            session()->get('messages')[0]['message']
        );
        $this->assertDatabaseMissing('questions', [
            'level_id' => $this->level->id,
            'question_text' => 'New Question 1'
        ]);
    }

    public function test_update_question_success()
    {
        // Arrange
        $data = [
            'question_text' => 'Updated Question',
            'duration' => 45,
            'max_score' => 15,
            'perfect_response' =>"001",

        ];

        // Act
        $response = $this->patch(route('admin.competitions.level.question.update', $this->question), $data);

        // Assert
        $response->assertRedirectBack();
        $this->assertDatabaseHas('questions', [
            'id' => $this->question->id,
            'question_text' => 'Updated Question',
            'duration' => 45,
            'max_score' => 15
        ]);
    }

    public function test_update_question_validation_failure()
    {
        // Arrange
        $data = [
            'question_text' => '',
            'duration' => 'invalid',
            'max_score' => 'invalid'
        ];

        // Act
        $response = $this->patch(route('admin.competitions.level.question.update', $this->question), $data);

        // Assert
        $response->assertSessionHasErrors(
            ['question_text', 'duration', 'max_score'],
            errorBag: 'updateQuestion'.$this->question->id
        );
    }

    public function test_update_question_active_level_failure()
    {
        // Arrange
        $this->level->update(['status' => 'active']); // Make level active
        $data = [
            'question_text' => 'Updated Question',
            'duration' => 45,
            'max_score' => 15
        ];

        // Act
        $response = $this->patch(route('admin.competitions.level.question.update', $this->question), $data);

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseMissing('questions', [
            'id' => $this->question->id,
            'question_text' => 'Updated Question'
        ]);
    }

} 