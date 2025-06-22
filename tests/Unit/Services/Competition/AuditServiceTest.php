<?php

namespace Tests\Unit\Services\Competition;

use Mockery;
use Exception;
use Tests\TestCase;
use App\Models\User;
use App\Models\Admin\Admin;
use App\Models\Competition\Level;
use Illuminate\Support\Collection;
use App\Contracts\FlasherInterface;
use App\Models\Competition\Question;
use App\Models\Competition\Response;
use Illuminate\Support\Facades\Auth;
use App\Services\Competition\AuditService;
use App\Contracts\TransactionManagerInterface;
use App\Interface\Competition\AuditRepositoryInterface;

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
    /** @var Level | Mockery\MockInterface */
    protected Level $level;
    /** @var User | Mockery\MockInterface */
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repositoryMock = Mockery::mock(AuditRepositoryInterface::class);
        $this->transactionManagerMock = Mockery::mock(TransactionManagerInterface::class);
        $this->flasherMock = Mockery::mock(FlasherInterface::class);
        $this->service = new AuditService(
            $this->repositoryMock,
            $this->transactionManagerMock,
            $this->flasherMock
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
        parent::tearDown();
    }

    public function test_audit_user_responses_returns_expected_array()
    {
        
        $questions = ['q1', 'q2'];

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
            $this->flasherMock
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
            $this->flasherMock
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
    /*public function test_submit_audit_unauthorized_admin()
    {
        $level = Level::factory()->make(['id' => 6]);
        $user = User::factory()->make(['id' => 7]);
        $responses = ['scores' => [1 => 10]];
        $this->repositoryMock->shouldReceive('isAdminAllowedToAuditUser')->with($level, $user)->andReturn(false);
        $this->flasherMock->shouldReceive('notifyCrudResult')->with(false, 'error')->once();
        $result = $this->service->submitAudit($responses, $level, $user);
        $this->assertFalse($result);
    }

    public function test_submit_audit_exception_handling()
    {
        $level = Level::factory()->make(['id' => 8]);
        $user = User::factory()->make(['id' => 9]);
        $responses = ['scores' => [1 => 10]];
        $this->repositoryMock->shouldReceive('isAdminAllowedToAuditUser')->with($level, $user)->andReturn(true);
        $this->transactionManagerMock->shouldReceive('run')->once()->andThrow(new Exception('DB error'));
        $this->flasherMock->shouldReceive('notifyCrudResult')->with(false, 'error')->once();
        $result = $this->service->submitAudit($responses, $level, $user);
        $this->assertFalse($result);
    }*/

    // ... more tests for each method will follow ...
} 