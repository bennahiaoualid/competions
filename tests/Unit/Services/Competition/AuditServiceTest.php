<?php

namespace Tests\Unit\Services\Competition;

use Mockery;
use Exception;
use Tests\TestCase;
use App\Models\User;
use App\Models\Admin\Admin;
use App\Models\Competition\Level;
use App\Contracts\FlasherInterface;
use Illuminate\Support\Facades\Auth;
use App\Services\Competition\AuditService;
use App\Contracts\TransactionManagerInterface;
use App\Interface\Competition\AuditRepositoryInterface;
use App\Models\Competition\Response;
use App\Services\CashManagment\CompetitionCacheManagmentSystem;
use Barryvdh\LaravelIdeHelper\Macro;
use PHPUnit\Framework\MockObject\Generator\MockType;
use Carbon\Carbon;

class AuditServiceTest extends TestCase
{
    /** @var AuditService */
    protected $service;
    /** @var AuditRepositoryInterface | Mockery\MockInterface */
    protected $repositoryMock;
    /** @var TransactionManagerInterface | Mockery\MockInterface */
    protected $transactionManagerMock;
    /** @var FlasherInterface | Mockery\MockInterface */
    protected $flasherMock;
     /** @var CompetitionCacheManagmentSystem | Mockery\MockInterface */
    protected $competitionCache;
    /** @var Level | Mockery\MockInterface */
    protected Level $level;
    /** @var User | Mockery\MockInterface */
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repositoryMock = Mockery::mock(AuditRepositoryInterface::class);
        $this->transactionManagerMock = Mockery::mock(TransactionManagerInterface::class);
        $this->competitionCache = Mockery::mock(CompetitionCacheManagmentSystem::class);
        $this->flasherMock = Mockery::mock(FlasherInterface::class);

        $this->service = new AuditService(
            $this->repositoryMock,
            $this->transactionManagerMock,
            $this->flasherMock,
            $this->competitionCache
        );

        // mock level
        $this->level = Mockery::mock(Level::class);
        $this->level->shouldReceive('getAttribute')->with('id')->andReturn(1);
        // Mock Auth facade
        $admin = Mockery::mock(Admin::class);
        $admin->shouldReceive('getAttribute')->with('id')->andReturn(1);
        // Mock user
        $this->user = Mockery::mock(User::class);
        $this->user->shouldReceive('getAttribute')->with('id')->andReturn(1);
        // Mock Auth facade
        Auth::shouldReceive('id')->andReturn(1);
        Auth::shouldReceive('user')->andReturn($admin);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_audit_user_responses_returns_expected_array()
    {
        
        // Create mock questions with responses
        $response1 = (object) ['admin_id' => 1];
        $response2 = (object) ['admin_id' => null];
        
        $question1 = (object) ['responses' => collect([$response1])];
        $question2 = (object) ['responses' => collect([$response2])];
        
        $questions = collect([$question1, $question2]);

        $this->repositoryMock->shouldReceive('getUser')
            ->once()
            ->with('user-identifier')
            ->andReturn($this->user);
        
            $this->repositoryMock->shouldReceive('getLevelQuestionsWithUserResponses')
            ->once()
            ->with($this->level->id, $this->user->id)
            ->andReturn($questions);
        
        $result = $this->service->auditUserResponses($this->level, 'user-identifier');
        
        $this->assertEquals($this->level, $result['level']);
        $this->assertEquals($this->user, $result['user']);
        $this->assertEquals($questions, $result['questions']);
        $this->assertFalse($result['is_all_audited']);
    }

    public function test_audit_user_responses_aborts_if_user_not_found()
    {
        $userIdentifier = 'non-existent-id';

        $this->repositoryMock
            ->shouldReceive('getUser')
            ->once()
            ->with($userIdentifier)
            ->andReturn(null);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $this->expectExceptionMessage('User not found');

        $this->service->auditUserResponses($this->level, $userIdentifier);
    }


    /*public function test_submit_audit_success()
    {
        // Arrange
        $responses = ['scores' => [1 => 10, 2 => 8]];

        $responses_origin = collect([]);

        $this->repositoryMock->shouldReceive('isAdminAllowedToAuditUser')
            ->once()
            ->with($this->level, $this->user)
            ->andReturnTrue();

        $this->transactionManagerMock->shouldReceive('run')
            ->once()
            ->with(Mockery::type('callable'))
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $this->repositoryMock->shouldReceive('getTargetedUserResponses')
            ->once()
            ->with($this->level->id, $this->user->id, $responses['scores'])
            ->andReturn($responses_origin);

       
        $this->flasherMock->shouldReceive('notify')->with('msg', 'success')->once();

        // Act
        $result = $this->service->submitAudit($responses, $this->level, $this->user);

        // Assert
        $this->assertNull($result); // No explicit return on success
    }*/
    public function test_submit_audit_success_with_multiple_responses()
    {
        // Arrange
        $responses = [
            'scores' => [
                1 => 85,
                2 => 92,
                3 => 78
            ]
        ];

        $responses_origin = collect([
            (object)['id' => 1],
            (object)['id' => 2],
            (object)['id' => 3]
        ]);

        $expectedMessages = [
            ['Audit submitted successfully', 'success'],
            ['3 responses updated', 'info']
        ];

        // Mock repository calls - authorization check happens first
        $this->repositoryMock->shouldReceive('isAdminAllowedToAuditUser')
            ->once()
            ->with($this->level, $this->user)
            ->andReturn(true);

        // This call happens INSIDE the transaction callback
        $this->repositoryMock->shouldReceive('getTargetedUserResponses')
            ->once()
            ->with($this->level->id, $this->user->id, $responses['scores'])
            ->andReturn($responses_origin);

        // Create a partial mock to override the trait method
        /** @var AuditService|Mockery\MockInterface */
        $partialMock = Mockery::mock(AuditService::class, [
            $this->repositoryMock,
            $this->transactionManagerMock,
            $this->flasherMock,
            $this->competitionCache
        ])->makePartial();

        // Mock the trait method to return expected messages
        $partialMock->shouldReceive('calculateUserResponseFinalScores')
            ->once()
            ->with($responses_origin, $responses['scores'])
            ->andReturn($expectedMessages);

        // Mock transaction manager to actually execute the callback
        $this->transactionManagerMock->shouldReceive('run')
            ->once()
            ->with(Mockery::type('callable'))
            ->andReturnUsing(function ($callback) {
                // Actually execute the callback to trigger the repository call
                return $callback();
            });

        // Mock flasher calls for each expected message
        $this->flasherMock->shouldReceive('notify')
            ->with('Audit submitted successfully', 'success')
            ->once();
        
        $this->flasherMock->shouldReceive('notify')
            ->with('3 responses updated', 'info')
            ->once();

        $this->competitionCache->shouldReceive('invalidateUsersAuditingInfo')
            ->with(Mockery::any())
            ->once();

        // Act
        $result = $partialMock->submitAudit($responses, $this->level, $this->user);

        // Assert
        $this->assertTrue($result); // Method doesn't return anything on success
    }

    public function test_submit_audit_success_with_single_response()
    {
        // Arrange
        $responses = [
            'scores' => [
                1 => 90
            ]
        ];

        $responses_origin = collect([
            (object)['id' => 1]
        ]);

        $expectedMessages = [
            ['Audit submitted successfully', 'success']
        ];

        // Mock repository calls
        $this->repositoryMock->shouldReceive('isAdminAllowedToAuditUser')
            ->once()
            ->with($this->level, $this->user)
            ->andReturn(true);

        $this->repositoryMock->shouldReceive('getTargetedUserResponses')
            ->once()
            ->with($this->level->id, $this->user->id, $responses['scores'])
            ->andReturn($responses_origin);

        // Create a partial mock to override the trait method
        /** @var AuditService|Mockery\MockInterface */
        $partialMock = Mockery::mock(AuditService::class, [
            $this->repositoryMock,
            $this->transactionManagerMock,
            $this->flasherMock,
            $this->competitionCache,
        ])->makePartial();

        // Mock the trait method
        $partialMock->shouldReceive('calculateUserResponseFinalScores')
            ->once()
            ->with($responses_origin, $responses['scores'])
            ->andReturn($expectedMessages);

        // Mock transaction manager
        $this->transactionManagerMock->shouldReceive('run')
            ->once()
            ->with(Mockery::type('callable'))
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        // Mock flasher calls
        $this->flasherMock->shouldReceive('notify')
            ->with('Audit submitted successfully', 'success')
            ->once(); 

        $this->competitionCache->shouldReceive('invalidateUsersAuditingInfo')
            ->with(Mockery::any())
            ->once();

        // Act
        $result = $partialMock->submitAudit($responses, $this->level, $this->user);

        // Assert
        $this->assertTrue($result);
    }

    public function test_submit_audit_fails_when_admin_not_allowed()
    {
        // Arrange
        $responses = [
            'scores' => [
                1 => 85,
                2 => 92
            ]
        ];

        // Mock repository to return false for admin permission
        $this->repositoryMock->shouldReceive('isAdminAllowedToAuditUser')
            ->once()
            ->with($this->level, $this->user)
            ->andReturn(false);

        // Mock flasher to expect error notification
        $this->flasherMock->shouldReceive('notifyCrudResult')
            ->once()
            ->with(false, 'error');

        // Transaction manager should not be called
        $this->transactionManagerMock->shouldNotReceive('run');

        // Act
        $result = $this->service->submitAudit($responses, $this->level, $this->user);

        // Assert
        $this->assertFalse($result);
    }

    public function test_submit_audit_handles_database_exception()
    {
        // Arrange
        $responses = [
            'scores' => [
                1 => 85
            ]
        ];

        // Mock repository calls
        $this->repositoryMock->shouldReceive('isAdminAllowedToAuditUser')
            ->once()
            ->with($this->level, $this->user)
            ->andReturn(true);

        // Mock transaction manager to throw exception
        $this->transactionManagerMock->shouldReceive('run')
            ->once()
            ->with(Mockery::type('callable'))
            ->andThrow(new Exception('Database connection failed'));

        // Mock flasher to expect error notification
        $this->flasherMock->shouldReceive('notifyCrudResult')
            ->once()
            ->with(false, 'error');

        // Act
        $result = $this->service->submitAudit($responses, $this->level, $this->user);

        // Assert
        $this->assertFalse($result);
    }

    public function test_submit_audit_handles_repository_exception()
    {
        // Arrange
        $responses = [
            'scores' => [
                1 => 85
            ]
        ];

        // Mock repository to throw exception during permission check
        $this->repositoryMock->shouldReceive('isAdminAllowedToAuditUser')
            ->once()
            ->with($this->level, $this->user)
            ->andThrow(new Exception('Repository error'));

        // Mock flasher to expect error notification
        $this->flasherMock->shouldReceive('notifyCrudResult')
            ->once()
            ->with(false, 'error');

        // Transaction manager should not be called
        $this->transactionManagerMock->shouldNotReceive('run');

        // Act
        $result = $this->service->submitAudit($responses, $this->level, $this->user);

        // Assert
        $this->assertFalse($result);
    }
 
    // New tests for assignAuditorsToResponsesForLevel
    public function test_assign_auditors_returns_false_when_level_cannot_edit()
    {
        $this->level->shouldReceive('canEdit')->once()->andReturn(false);

        $this->flasherMock->shouldReceive('error')->once();

        $result = $this->service->assignAuditorsToResponsesForLevel($this->level);

        $this->assertFalse($result);
    }

    public function test_assign_auditors_returns_false_when_too_early()
    {
        Carbon::setTestNow(now());

        $this->level->shouldReceive('canEdit')->once()->andReturn(true);

        $finishedAt = now()->subMinutes(40);
        $competition = (object) ['auditing_time_for_level' => 30];

        $this->level->shouldReceive('getAttribute')->with('finished_at')->andReturn($finishedAt);
        $this->level->shouldReceive('getAttribute')->with('competition')->andReturn($competition);


        $this->flasherMock->shouldReceive('error')->once();

        $result = $this->service->assignAuditorsToResponsesForLevel($this->level);

        $this->assertFalse($result);
    }

    public function test_assign_auditors_success_with_updates_and_notifications()
    {
        Carbon::setTestNow(now());

        $this->level->shouldReceive('canEdit')->once()->andReturn(true);

        $finishedAt = now()->subHour();
        $competition = (object) ['auditing_time_for_level' => 30];
        $this->level->shouldReceive('getAttribute')->with('finished_at')->andReturn($finishedAt);
        $this->level->shouldReceive('getAttribute')->with('competition')->andReturn($competition);


        $this->repositoryMock->shouldReceive('assignAuditorsToResponsesForLevel')
            ->once()
            ->with($this->level->id)
            ->andReturn(5);

        $this->flasherMock->shouldReceive('success')->once();

        $this->competitionCache->shouldReceive('invalidateUsersAuditingInfo')
            ->with(Mockery::any())
            ->once();

        $result = $this->service->assignAuditorsToResponsesForLevel($this->level);

        $this->assertSame(5, $result);
    }

    public function test_assign_auditors_info_when_nothing_to_update()
    {
        Carbon::setTestNow(now());

        $this->level->shouldReceive('canEdit')->once()->andReturn(true);

        $finishedAt = now()->subHour();
        $competition = (object) ['auditing_time_for_level' => 30];
        $this->level->shouldReceive('getAttribute')->with('finished_at')->andReturn($finishedAt);
        $this->level->shouldReceive('getAttribute')->with('competition')->andReturn($competition);


        $this->repositoryMock->shouldReceive('assignAuditorsToResponsesForLevel')
            ->once()
            ->with($this->level->id)
            ->andReturn(0);

        $this->flasherMock->shouldReceive('info')->once();

        $this->competitionCache->shouldReceive('invalidateUsersAuditingInfo')
            ->with(Mockery::any())
            ->once();

        $result = $this->service->assignAuditorsToResponsesForLevel($this->level);

        $this->assertSame(0, $result);
    }

    public function test_assign_auditors_handles_exception_and_returns_false()
    {
        Carbon::setTestNow(now());

        $this->level->shouldReceive('canEdit')->once()->andReturn(true);

        $finishedAt = now()->subHour();
        $competition = (object) ['auditing_time_for_level' => 30];
        $this->level->shouldReceive('getAttribute')->with('finished_at')->andReturn($finishedAt);
        $this->level->shouldReceive('getAttribute')->with('competition')->andReturn($competition);



        $this->repositoryMock->shouldReceive('assignAuditorsToResponsesForLevel')
            ->once()
            ->with($this->level->id)
            ->andThrow(new Exception('DB error'));

        $this->flasherMock->shouldReceive('error')->once();

        $result = $this->service->assignAuditorsToResponsesForLevel($this->level);

        $this->assertFalse($result);
    }

    // New tests for processAndStoreAIBatchScores
    public function test_process_and_store_ai_batch_scores_success()
    {
        $level = Mockery::mock(Level::class);
        $level->shouldReceive('getAttribute')->with('id')->andReturn(10);

        $users = collect([
            (object)['id' => 1],
            (object)['id' => 2],
        ]);

        $questions = collect([
            (object)['id' => 100, 'max_score' => 100, 'duration' => 100],
            (object)['id' => 200, 'max_score' => 50, 'duration' => 100],
        ]);

        $responses = collect([
            Response::factory()->make([
                'id' => 1001,
                'user_id' => 1,
                'question_id' => 100,
                'response_duration' => 60,
                'penalty' => 0,
            ]),
            Response::factory()->make([
                'id' => 1002,
                'user_id' => 2,
                'question_id' => 100,
                'response_duration' => 60,
                'penalty' => 0,
            ]),
            Response::factory()->make([
                'id' => 2001,
                'user_id' => 1,
                'question_id' => 200,
                'response_duration' => 40,
                'penalty' => 0,
            ]),
        ]);

        $aiScores = [
            'questions_audited' => [
                [
                    'question_id' => 100,
                    'user_responses_audited' => [
                        ['user_id' => 1, 'user_response_score' => 90],
                        ['user_id' => 2, 'user_response_score' => 80],
                    ],
                ],
                [
                    'question_id' => 200,
                    'user_responses_audited' => [
                        ['user_id' => 1, 'user_response_score' => 40],
                    ],
                ],
            ],
        ];

        $this->repositoryMock->shouldReceive('bulkUpdateResponses')
            ->once()
            ->with(Mockery::any(),Mockery::any(),Mockery::any());

        // Execute transaction
        $this->transactionManagerMock->shouldReceive('run')
            ->once()
            ->with(Mockery::type('callable'))
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $result = $this->service->processAndStoreAIBatchScores($level, $users, $questions, $aiScores, $responses);

        $this->assertTrue($result['success']);
        $this->assertSame(2, $result['processed_users']);
        $this->assertSame(3, $result['processed_responses']);
        $this->assertCount(0, $result['errors']);
        $this->assertNotEmpty($result['notifications']);
    }

    public function test_process_and_store_ai_batch_scores_collects_errors_and_still_returns_result()
    {
        $level = Mockery::mock(Level::class);
        $level->shouldReceive('getAttribute')->with('id')->andReturn(11);

        $users = collect([
            (object)['id' => 1],
        ]);
        $questions = collect([
            (object)['id' => 100, 'max_score' => 100, 'duration' => 100],
        ]);

        $responses = collect([
            Response::factory()->make([
                'id' => 1001,
                'user_id' => 1,
                'question_id' => 100,
                'response_duration' => 60,
                'penalty' => 0,
            ]),
        ]);

        $aiScores = [
            'questions_audited' => [
                [
                    'question_id' => 999, // Missing question
                    'user_responses_audited' => [
                        ['user_id' => 1, 'user_response_score' => 10],
                    ],
                ],
                [
                    'question_id' => 100,
                    'user_responses_audited' => [
                        ['user_id' => 3, 'user_response_score' => 10], // Missing user
                        ['user_id' => 1, 'user_response_score' => 150], // Exceeds max
                        ['user_id' => 1, 'user_response_score' => 50], // Valid
                    ],
                ],
            ],
        ];

        $this->repositoryMock->shouldReceive('bulkUpdateResponses')
            ->once()
            ->with(Mockery::on(function ($bulkData) {
                return is_array($bulkData) && count($bulkData) === 1;
            }), [1], [100]);

        $this->transactionManagerMock->shouldReceive('run')
            ->once()
            ->with(Mockery::type('callable'))
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $result = $this->service->processAndStoreAIBatchScores($level, $users, $questions, $aiScores, $responses);

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['processed_users']);
        $this->assertSame(1, $result['processed_responses']);
        $this->assertGreaterThanOrEqual(3, count($result['errors']));
        $this->assertNotEmpty($result['notifications']);
    }
} 