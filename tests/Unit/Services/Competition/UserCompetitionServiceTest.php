<?php

namespace Tests\Unit\Services\User;

use Mockery;
use Exception;
use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User;
use App\Models\Competition\Level;
use Illuminate\Support\Collection;
use App\Contracts\FlasherInterface;
use App\Models\Competition\Question;
use App\Models\Competition\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\Competition\Competition;
use Illuminate\Foundation\Testing\WithFaker;
use App\Services\User\UserCompetitionService;
use App\Contracts\TransactionManagerInterface;
use App\Interface\User\UserCompetitionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class UserCompetitionServiceTest extends TestCase
{
    use WithFaker;
    /** @var UserCompetitionRepositoryInterface | Mockery\MockInterface */
    protected $userCompetitionRepository;
    /** @var TransactionManagerInterface | Mockery\MockInterface */
    protected $transactionManager;
    /** @var FlasherInterface | Mockery\MockInterface */
    protected $flasher;
    /** @var UserCompetitionService */
    protected $userCompetitionService;
    /** @var Competition | Mockery\MockInterface */
    protected $competition;
    /** @var Level | Mockery\MockInterface */
    protected $level;
    protected $userId;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->userCompetitionRepository = Mockery::mock(UserCompetitionRepositoryInterface::class);
        $this->transactionManager = Mockery::mock(TransactionManagerInterface::class);
        $this->flasher = Mockery::mock(FlasherInterface::class);

        // Create service instance
        $this->userCompetitionService = new UserCompetitionService(
            $this->userCompetitionRepository,
            $this->transactionManager,
            $this->flasher
        );

        // Setup test data
        $this->userId = 1;

        // Mock Competition model
        $this->competition = Mockery::mock(Competition::class);
        $this->competition->shouldReceive('getAttribute')->with('id')->andReturn(1);
        
        // Mock Level model
        $this->level = Mockery::mock(Level::class)->makePartial();
        $this->level->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $this->level->shouldReceive('getAttribute')->with('questions_number')->andReturn(10);
        $this->level->shouldReceive('getAttribute')->with('status')->andReturn('active');
        $this->level->shouldReceive('getAttribute')->with('competition')->andReturn($this->competition);


        // Mock Auth facade
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('id')->andReturn($this->userId);
        Auth::shouldReceive('id')->andReturn($this->userId);
        Auth::shouldReceive('user')->andReturn($user);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // prepare questions
    private function prepareQuestions($collection = false , $number = 0)
    {
        if($collection){
            $questions = new EloquentCollection();
            for($i = 1; $i <= $number; $i++){
                $question = Mockery::mock(Question::class);
                $question->shouldReceive('getAttribute')->with('id')->andReturn($i);
                $questions->push($question);
            }
            return $questions;
        }
        else{
            $question = Mockery::mock(Question::class);
            $question->shouldReceive('getAttribute')->with('id')->andReturn(1);
            return $question;
        }
    }

    public function test_level_start_success_with_questions()
    {
         // Arrange
        $mockUsers = Mockery::mock(Collection::class);
        $mockUsers->shouldReceive('contains')
            ->with($this->userId)
            ->andReturn(true);
        $this->competition->shouldReceive('getAttribute')->with('users')->andReturn($mockUsers);

        $selectedQuestion = $this->prepareQuestions();
        $questions = Mockery::mock(EloquentCollection::class)->makePartial();
        $questions->shouldReceive('count')->andReturn(3);
        $questions->shouldReceive('random')->andReturn($selectedQuestion);
        $questions->shouldReceive('isEmpty')->andReturn(false);

        $response = Mockery::mock(Response::class);

        $this->userCompetitionRepository
            ->shouldReceive('getUnansweredQuestions')
            ->once()
            ->with($this->level->id, $this->userId)
            ->andReturn($questions);

        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->with(Mockery::type('callable'))
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $this->userCompetitionRepository
            ->shouldReceive('createResponse')
            ->once()
            ->with([
                'response_text' => '',
                'question_id' => $selectedQuestion->id,
                'user_id' => $this->userId,
                'admin_id' => null,
            ])
            ->andReturn($response);

        // Act
        $result = $this->userCompetitionService->beginUserLevelAttempt($this->level);

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals('success', $result['status']);
        $this->assertEquals($selectedQuestion, $result['question']);
        $this->assertEquals($this->level, $result['level']);

        // Verify session was set
        $this->assertNotNull(session('start_time'));
    }

    public function test_level_start_empty_questions()
    {
        // Arrange
        $mockUsers = Mockery::mock(Collection::class);
        $mockUsers->shouldReceive('contains')
            ->with($this->userId)
            ->andReturn(true);
        $this->competition->shouldReceive('getAttribute')->with('users')->andReturn($mockUsers);

        $questions = Mockery::mock(EloquentCollection::class)->makePartial();
        $questions->shouldReceive('isEmpty')->andReturn(true);
        $this->userCompetitionRepository->shouldReceive('getUnansweredQuestions')
            ->once()
            ->with($this->level->id, $this->userId)
            ->andReturn($questions);
        
        // Act
        $result = $this->userCompetitionService->beginUserLevelAttempt($this->level);

        // Assert
        $this->assertEquals('empty', $result['status']);
    }

    public function test_level_start_user_not_participate_in_this_level_competition()
    {
        // Arrange
        $mockUsers = Mockery::mock(Collection::class);
        $mockUsers->shouldReceive('contains')
            ->with($this->userId)
            ->andReturn(false);

        $this->competition->shouldReceive('getAttribute')->with('users')->andReturn($mockUsers);
        
        $this->flasher->shouldReceive('notifyCrudResult')
            ->with(false, 'something_went_wrong')
            ->once();
        // Act
        $result = $this->userCompetitionService->beginUserLevelAttempt($this->level);

        // Assert
        $this->assertEquals('error', $result['status']);
    }

    public function test_level_start_exception_handling()
    {
        // Arrange
        $mockUsers = Mockery::mock(Collection::class);
        $mockUsers->shouldReceive('contains')
            ->with($this->userId)
            ->andReturn(true);
        $this->competition->shouldReceive('getAttribute')->with('users')->andReturn($mockUsers);

        $this->userCompetitionRepository->shouldReceive('getUnansweredQuestions')
            ->once()
            ->with($this->level->id, 1)
            ->andThrow(new Exception('DB error'));

        $this->flasher->shouldReceive('notifyCrudResult')
            ->with(false, 'something_went_wrong')
            ->once();

        // Act
        $result = $this->userCompetitionService->beginUserLevelAttempt($this->level);
        
        // Assert
        $this->assertEquals('error', $result['status']);
    }

    public function test_store_response_success()
    {
        // Arrange
        $startTime = Carbon::now()->subSeconds(30);
        session(['start_time' => $startTime]);

        $data = [
            'response_text' => 'This is my answer to the question',
            'keystrokes' => 150
        ];

        $question = $this->prepareQuestions();

        $expectedResponseTime = 30.0; // 30 seconds
        $expectedPenaltyFlags = [
            'flags' => json_encode([]),
            'penalty' => 0
        ];

        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->with(Mockery::type('callable'))
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $this->userCompetitionRepository
            ->shouldReceive('updateResponse')
            ->once()
            ->with(
                $question->id,
                array_merge($expectedPenaltyFlags, [
                    'response_text' => $data['response_text'],
                    'response_duration' => $expectedResponseTime,
                    'keystrokes' => $data['keystrokes'],
                ])
            )
            ->andReturn(true);

        // Act
        $result = $this->userCompetitionService->storeResponse($question, $data);

        // Assert
        $this->assertTrue($result);
        $this->assertNull(session('start_time')); // Session should be cleared

    }

    public function test_store_response_throws_if_start_time_missing()
    {
        // Arrange
        $data = ['response_text' => 'any', 'keystrokes' => 0];
        $question = $this->prepareQuestions();
        $this->flasher->shouldReceive('notifyCrudResult')
            ->with(false, 'something_went_wrong')
            ->once();

        // Act
        $result = $this->userCompetitionService->storeResponse($question, $data);

        // Assert
        $this->assertFalse($result);
    }
} 