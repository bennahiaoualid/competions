<?php

namespace Tests\Unit;

use Mockery;
use Exception;
use Tests\TestCase;
use App\Models\Competition\Level;
use Illuminate\Support\Collection;
use App\Contracts\FlasherInterface;
use App\Models\Competition\Question;
use App\Services\Competition\QuestionService;
use App\Contracts\TransactionManagerInterface;
use App\Interface\Competition\QuestionRepositoryInterface;

class QuestionServiceTest extends TestCase
{
    /** @var QuestionService&\Mockery\MockInterface */
    protected $questionService;
    /** @var QuestionRepositoryInterface&\Mockery\MockInterface */
    protected $questionRepository;
    /** @var TransactionManagerInterface&\Mockery\MockInterface */
    protected $transactionManager;
    /** @var FlasherInterface&\Mockery\MockInterface */
    protected $flasher;
    /** @var Level|\Mockery\MockInterface */
    protected $level;
    /** @var Question|\Mockery\MockInterface */
    protected $question;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mock repository & transaction manager & flasher
        $this->questionRepository = Mockery::mock(QuestionRepositoryInterface::class);
        $this->transactionManager = Mockery::mock(TransactionManagerInterface::class);
        $this->flasher = Mockery::mock(FlasherInterface::class);
        
        $this->questionService = new QuestionService(
            $this->questionRepository,
            $this->transactionManager,
            $this->flasher
        );

        // Mock Level model
        $this->level = Mockery::mock(Level::class)->makePartial();
        $this->level->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $this->level->name = 'Test Level';
        $this->question = Mockery::mock(Question::class);
        $this->question->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $this->question->shouldReceive('getAttribute')->with('level_id')->andReturn(1);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_all_questions_success()
    {
        // Arrange
        $levelId = 1;
        $levelIdBase64 = base64_encode($levelId);
        $perPage = 10;
        $questions = collect([new Question()]);

        $this->questionRepository
            ->shouldReceive('findOrFailLevel')
            ->with($levelId)
            ->once()
            ->andReturn($this->level);

        $this->questionRepository
            ->shouldReceive('getQuestionsByLevel')
            ->with($levelId, $perPage)
            ->once()
            ->andReturn($questions);

        // Act
        $result = $this->questionService->all($this->level);

        // Assert
        $this->assertInstanceOf(\Illuminate\View\View::class, $result);
    }

    public function test_create_questions_success()
    {
        // Arrange
        $data = [
            'level_id' => 1,
            'question_text' => ['Question 1', 'Question 2'],
            'duration' => [30, 45],
            'max_score' => [10, 15]
        ];

        $level = $this->level;

        $mock_questions = Mockery::mock(Collection::class);
        $mock_questions->shouldReceive('count')->andReturn(0);

        $level->shouldReceive('getAttribute')->with('questions_number')->andReturn(2);
        $level->shouldReceive('getAttribute')->with('questions')->andReturn($mock_questions);
        $level->shouldReceive('canEditQuestion')->andReturn(true);
        $level->status = 0;

        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $this->questionRepository
            ->shouldReceive('insert')
            ->once()
            ->andReturn(true);

        $this->flasher
            ->shouldReceive('notifyCrudResult')
            ->with(true, 'saved')
            ->once();

        // Act
        $result = $this->questionService->create($data, $level);

        // Assert
        $this->assertTrue($result);
    }

    public function test_create_questions_failure_not_allowed()
    {
        // Arrange
        $data = [
            'level_id' => 1,
            'question_text' => ['Question 1'],
            'duration' => [30],
            'max_score' => [10]
        ];

        $level = $this->level;
        $level->shouldReceive('canEditQuestion')->andReturn(false);

        $this->flasher
            ->shouldReceive('notify')
            ->with(__('messages.validation.not_allow.question_update'), 'error')
            ->once();

        // Act
        $result = $this->questionService->create($data, $level);

        // Assert
        $this->assertFalse($result);
    }

    public function test_create_questions_failure_active_level()
    {
        // Arrange
        $data = [
            'level_id' => 1,
            'question_text' => ['Question 1'],
            'duration' => [30],
            'max_score' => [10]
        ];

        $level = $this->level;
        $level->shouldReceive('canEditQuestion')->andReturn(true);
        $level->status = 1;


        $this->flasher
            ->shouldReceive('notify')
            ->with(__('messages.validation.not_allow.active_level_update'), 'error')
            ->once();

        // Act
        $result = $this->questionService->create($data, $level);

        // Assert
        $this->assertFalse($result);
    }

    public function test_create_questions_failure_invalid_questions()
    {
        // Arrange
        $data = [
            'level_id' => 1,
        ];
        // Act
        $result = $this->questionService->create($data, $this->level);

        // Assert
        $this->assertFalse($result);
    }

    public function test_create_questions_failure_invalid_question_number()
    {
        // Arrange
        $data = [
            'level_id' => 1,
            'question_text' => ['Question 1', 'Question 2', 'Question 3'],
            'duration' => [30, 45, 60],
            'max_score' => [10, 15, 20]
        ];
        $level = $this->level;
        $level->shouldReceive('getAttribute')->with('questions_number')->andReturn(3);
        
        $mock_questions = Mockery::mock(Collection::class);
        $mock_questions->shouldReceive('count')->andReturn(2);
        $level->shouldReceive('getAttribute')->with('questions')->andReturn($mock_questions);
        $level->shouldReceive('canEditQuestion')->andReturn(true);
        $level->status = 0;

        $this->flasher
            ->shouldReceive('notify')
            ->with(__('messages.validation.not_allow.question_update_max_number',['number' => $level->questions_number]), 'error')
            ->once();
        // Act
        $result = $this->questionService->create($data, $level);

        // Assert
        $this->assertFalse($result);
    }
    public function test_update_question_success()
    {
        // Arrange
        $question = $this->question;
        $data = [
            'question_text' => 'Updated Question',
            'duration' => 45,
            'max_score' => 15
        ];

        $level = $this->level;
        $level->shouldReceive('canEditQuestion')->andReturn(true);
        $level->status = 0;

        $this->questionRepository
            ->shouldReceive('findOrFailLevel')
            ->with($question->level_id)
            ->once()
            ->andReturn($level);

        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $this->questionRepository
            ->shouldReceive('update')
            ->with($question, $data)
            ->once()
            ->andReturn(true);

        $this->flasher
            ->shouldReceive('notifyCrudResult')
            ->with(true, 'saved')
            ->once();

        // Act
        $result = $this->questionService->update($question, $data);

        // Assert
        $this->assertTrue($result);
    }

    public function test_update_question_failure_not_allowed()
    {
        // Arrange
        $question = $this->question;
        $data = [
            'question_text' => 'Updated Question',
            'duration' => 45,
            'max_score' => 15
        ];

        $level = $this->level;
        $level->shouldReceive('canEditQuestion')->andReturn(false);

        $this->questionRepository
            ->shouldReceive('findOrFailLevel')
            ->with($question->level_id)
            ->once()
            ->andReturn($level);

        $this->flasher
            ->shouldReceive('notify')
            ->with(__('messages.validation.not_allow.question_update'), 'error')
            ->once();

        // Act
        $result = $this->questionService->update($question, $data);

        // Assert
        $this->assertFalse($result);
    }

    public function test_update_question_failure_active_level()
    {
        // Arrange
        $question = $this->question;
        $data = [
            'question_text' => 'Updated Question',
            'duration' => 45,
            'max_score' => 15
        ];

        $level = $this->level;
        $level->shouldReceive('canEditQuestion')->andReturn(true);
        $level->status = 1;

        $this->questionRepository
            ->shouldReceive('findOrFailLevel')
            ->with($question->level_id)
            ->once()
            ->andReturn($level);

        $this->flasher
            ->shouldReceive('notify')
            ->with(__('messages.validation.not_allow.active_level_update'), 'error')
            ->once();

        // Act
        $result = $this->questionService->update($question, $data);

        // Assert
        $this->assertFalse($result);
    }

    public function test_update_question_failure_exception()
    {
        // Arrange
        $question = $this->question;
        $data = [
            'question_text' => 'Updated Question',
            'duration' => 45,
            'max_score' => 15
        ];

        $level = $this->level;
        $level->shouldReceive('canEditQuestion')->andReturn(true);
        $level->status = 0;

        $this->questionRepository
            ->shouldReceive('findOrFailLevel')
            ->with($question->level_id)
            ->once()
            ->andReturn($level);

        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andThrow(new Exception('Database error'));

        $this->flasher
            ->shouldReceive('notifyCrudResult')
            ->with(false, 'saved')
            ->once();

        // Act
        $result = $this->questionService->update($question, $data);

        // Assert
        $this->assertFalse($result);
    }
} 