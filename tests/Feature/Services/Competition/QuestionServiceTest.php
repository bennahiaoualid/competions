<?php

namespace Tests\Feature\Services\Competition;

use Tests\TestCase;
use App\Models\Admin\Admin;
use App\Models\Competition\Level;
use App\Contracts\FlasherInterface;
use App\Models\Competition\Question;
use Illuminate\Support\Facades\Auth;
use App\Services\Competition\QuestionService;
use App\Contracts\TransactionManagerInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class QuestionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;
    protected $transactionManager;
    protected $flasher;
    protected $mainAdmin;
    protected $mainLevel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transactionManager = \Mockery::mock(\App\Contracts\TransactionManagerInterface::class);
        $this->flasher = \Mockery::mock(\App\Contracts\FlasherInterface::class);
        $this->service = new QuestionService($this->transactionManager, $this->flasher);

        $this->mainAdmin = Admin::factory()->create();
        $this->mainLevel = Level::factory()->create(['questions_number' => 3, 'status' => 'pending']);
        Auth::shouldReceive('id')->andReturn($this->mainAdmin->id);

    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function test_all_returns_paginated_questions_for_level()
    {
        $level = Level::factory()->create();
        $questions = Question::factory()->count(3)->create(['level_id' => $level->id]);
        $service = $this->service;

        $result = $service->all($level);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(3, $result->total());
        $this->assertEquals($level->id, $result->first()->level_id);
    }

    public function test_create_inserts_questions_for_level_success()
    {
        $level = Level::factory()->create(['questions_number' => 3, 'status' => 'pending']);
        $data = [
            'question_text' => ['Q1', 'Q2'],
            'duration' => [30, 40],
            'max_score' => [10, 20],
        ];
        $this->transactionManager->shouldReceive('run')->andReturnUsing(fn($cb) => $cb());
        $this->flasher->shouldReceive('notifyCrudResult')->once()->with(true, 'saved');
        $service = $this->service;
        $result = $service->create($data, $level);
        $this->assertTrue($result);
        $this->assertDatabaseHas('questions', ['question_text' => 'Q1', 'level_id' => $level->id]);
        $this->assertDatabaseHas('questions', ['question_text' => 'Q2', 'level_id' => $level->id]);
    }

    public function test_create_fails_if_cannot_edit_question()
    {
        $admin = Admin::factory()->create();
        $level = Level::factory()->create(
            [
                'questions_number' => 3, 
                'status' => 'pending',
                'admin_id' => $admin->id
            ]
        );
        $data = [
            'question_text' => ['Q1'],
            'duration' => [30],
            'max_score' => [10],
        ];
        $this->flasher->shouldReceive('notify')
        ->once()
        ->with(__('messages.validation.not_allow.question_update'), 'error');
        $service = $this->service;
        // Simulate cannot edit
        $level->canEditQuestion = fn() => false;
        $this->assertFalse($service->create($data, $level));
    }

    public function test_create_fails_if_level_not_pending()
    {
        $level = Level::factory()->create(['questions_number' => 3, 'status' => 'active']);

        $data = [
            'question_text' => ['Q1'],
            'duration' => [30],
            'max_score' => [10],
        ];
        $this->flasher->shouldReceive('notify')
            ->once()
            ->with(__('messages.validation.not_allow.active_level_update'), 'error');
        $service = $this->service;
        // Simulate can edit
        $level->canEditQuestion = fn() => true;
        $this->assertFalse($service->create($data, $level));
    }

    public function test_create_fails_if_exceeds_question_number()
    {
        Question::factory()->count(3)->create(['level_id' => $this->mainLevel->id]);
        $data = [
            'question_text' => ['Q1'],
            'duration' => [30],
            'max_score' => [10],
        ];
        $this->flasher->shouldReceive('notify')
            ->once()
            ->with(__('messages.validation.not_allow.question_update_max_number', ['number' => $this->mainLevel->questions_number]), 'error');

        $this->assertFalse($this->service->create($data, $this->mainLevel));
    }

    public function test_update_success()
    {
        $question = Question::factory()->create(['level_id' => $this->mainLevel->id]);

        $data = ['question_text' => 'Updated', 'duration' => 50, 'max_score' => 30];
        $this->transactionManager->shouldReceive('run')->andReturnUsing(fn($cb) => $cb());
        $this->flasher->shouldReceive('notifyCrudResult')->once()->with(true, 'saved');
        
        $result = $this->service->update($question, $data);
        
        $this->assertTrue($result);
        $this->assertDatabaseHas('questions', ['id' => $question->id, 'question_text' => 'Updated']);
    }

    public function test_update_fails_if_cannot_edit()
    {
        $this->mainLevel->update(['admin_id' => (Admin::factory()->create())->id]);
        $question = Question::factory()->create(['level_id' => $this->mainLevel->id]);
        $this->flasher->shouldReceive('notify')
            ->once()
            ->with(__('messages.validation.not_allow.question_update'), 'error');
        $data = ['question_text' => 'Updated', 'duration' => 50, 'max_score' => 30];

        $result = $this->service->update($question, $data);

        $this->assertFalse($result);
    }

    public function test_update_fails_if_level_not_pending()
    {
        $this->mainLevel->update(['status' => 'active']);
        $question = Question::factory()->create(['level_id' => $this->mainLevel->id]);

        $this->flasher->shouldReceive('notify')
            ->once()
            ->with(__('messages.validation.not_allow.active_level_update'), 'error');
        $data = ['question_text' => 'Updated', 'duration' => 50, 'max_score' => 30];
        
        $result = $this->service->update($question, $data);
        $this->assertFalse($result);
    }

    public function test_validate_questions_number_true_and_false()
    {
        $service = $this->service;
        $level = Level::factory()->create(['questions_number' => 3]);
        $level->setRelation('questions', collect([1, 2]));
        $method = (new \ReflectionClass($service))->getMethod('validateQuestionsNumber');
        $method->setAccessible(true);
        $this->assertTrue($method->invoke($service, $level, 1));
        $this->assertFalse($method->invoke($service, $level, 2));
    }
} 