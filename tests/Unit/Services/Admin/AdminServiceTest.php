<?php

namespace Tests\Unit\Services\Admin;

use Bus;
use Mockery;
use Exception;
use Tests\TestCase;
use App\Models\Admin\Admin;
use App\Contracts\FlasherInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use App\Jobs\Competition\SafeDeleteAuditorJob;
use App\Contracts\TransactionManagerInterface;
use App\Services\Admin\AdminService;
use App\Services\Monitoring\JobTrackingService;
use App\Interface\Admin\AdminRepositoryInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Spatie\Permission\Models\Role;

class AdminServiceTest extends TestCase
{
    /** @var AdminService&\Mockery\MockInterface */
    protected $adminService;
    /** @var AdminRepositoryInterface&\Mockery\MockInterface */
    protected $adminRepository;
    /** @var TransactionManagerInterface&\Mockery\MockInterface */
    protected $transactionManager;
    /** @var FlasherInterface&\Mockery\MockInterface */
    protected $flasher;
    /** @var JobTrackingService&\Mockery\MockInterface */
    protected $jobTrackingService;
    /** @var Admin|\Mockery\MockInterface */
    protected $admin_partial;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mock dependencies
        $this->adminRepository = Mockery::mock(AdminRepositoryInterface::class);
        $this->transactionManager = Mockery::mock(TransactionManagerInterface::class);
        $this->flasher = Mockery::mock(FlasherInterface::class);
        $this->jobTrackingService = Mockery::mock(JobTrackingService::class);
        
        $this->adminService = new AdminService(
            $this->adminRepository,
            $this->transactionManager,
            $this->flasher,
            $this->jobTrackingService
        );

        $this->admin_partial = Mockery::mock(Admin::class);
        $this->admin_partial->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $this->admin_partial->shouldReceive('getAttribute')->with('name')->andReturn('Test Admin');
        $this->admin_partial->shouldReceive('getAttribute')->with('email')->andReturn('test@example.com');
        
        // Mock Auth facade
        Auth::shouldReceive('id')->andReturn(1);
        Auth::shouldReceive('user')->andReturn($this->mockAdmin('owner', true));
        
        // Fake queues for job testing
        Queue::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function createAdminData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Admin',
            'email' => 'test@example.com',
            'password' => 'password123',
            'birthdate' => '1990-01-01',
            'gender' => 'male',
        ], $overrides);
    }

    private function mockAdmin($role = 'owner', $hasRole = true): Admin | Mockery\MockInterface
    {
        $admin = Mockery::mock(Admin::class);
        $roles = Mockery::mock(EloquentCollection::class);
        
        if ($hasRole) {
            $roles->shouldReceive('pluck')->with('name')->andReturn(collect([$role]));
        } else {
            $roles->shouldReceive('pluck')->with('name')->andReturn(collect([]));
        }
        
        $admin->shouldReceive('getAttribute')->with('roles')->andReturn($roles);
        $admin->shouldReceive('__get')->with('roles')->andReturn($roles);
        
        return $admin;
    }

    // ==================== INDEX METHOD TESTS ====================

    public function test_index_returns_admin_and_user_counts()
    {
        // Arrange
        $adminCount = 5;
        $userCount = 10;

        $this->adminRepository
            ->shouldReceive('getAdminCount')
            ->once()
            ->andReturn($adminCount);

        $this->adminRepository
            ->shouldReceive('getUserCount')
            ->once()
            ->andReturn($userCount);

        // Act
        $result = $this->adminService->index();

        // Assert
        $this->assertEquals([
            'count' => [
                'admin' => $adminCount,
                'user' => $userCount,
            ]
        ], $result);
    }

    // ==================== ALL METHOD TESTS ====================

    public function test_adminList_returns_possible_roles()
    {
        // Arrange 
        $mockRoles = EloquentCollection::make([
            (object)['id' => 1, 'name' => 'admin'],
            (object)['id' => 2, 'name' => 'moderator']
        ]);
        
        // Mock the service to override the trait method
        /** @var AdminService&\Mockery\MockInterface */
        $adminService = Mockery::mock(AdminService::class)->makePartial();
        $adminService->shouldReceive('possibleRoles')
            ->once()
            ->andReturn($mockRoles);

        // Act
        $result = $adminService->adminList();

        // Assert
        $this->assertEquals(['roles' => $mockRoles], $result);
    }

    // ==================== CREATE METHOD TESTS ====================

    public function test_create_admin_success()
    {
        // Arrange
        $adminData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => [1, 2]
        ];
        
        $mockAdmin = $this->admin_partial;
        
        // Create a mock of the actual BelongsToMany relationship
        $mockRolesRelation = Mockery::mock(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
        $mockRolesRelation->shouldReceive('sync')
            ->with([1, 2])
            ->once()
            ->andReturn(['attached' => [1, 2], 'detached' => [], 'updated' => []]);
        
        $mockAdmin->shouldReceive('roles')
            ->once()
            ->andReturn($mockRolesRelation);

        $this->adminRepository
            ->shouldReceive('create')
            ->with($adminData)
            ->once()
            ->andReturn($mockAdmin);

        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) { 
                return $callback(); 
            });

        $this->flasher
            ->shouldReceive('notifyCrudResult')
            ->with(true, 'saved')
            ->once();

        // Act
        $result = $this->adminService->create($adminData);

        // Assert
        $this->assertTrue($result);
    }

    public function test_create_admin_failure()
    {
        // Arrange
        $adminData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => [1, 2]
        ];

        $this->adminRepository
            ->shouldReceive('create')
            ->with($adminData)
            ->once()
            ->andThrow(new Exception('Database error'));

        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) { 
                return $callback(); 
            });

        $this->flasher
            ->shouldReceive('notifyCrudResult')
            ->with(false, 'saved')
            ->once();

        // Act
        $result = $this->adminService->create($adminData);

        // Assert
        $this->assertFalse($result);
    }

    // ==================== EDIT METHOD TESTS ====================

    public function test_edit_returns_admin_and_roles()
    {
        // Arrange
        $admin = $this->admin_partial;
        $mockRoles = EloquentCollection::make([
            (object)['id' => 1, 'name' => 'admin'],
            (object)['id' => 2, 'name' => 'moderator']
        ]);

        // Create a new AdminService instance with mocked dependencies
        $adminService = new AdminService(
            $this->adminRepository,
            $this->transactionManager,
            $this->flasher,
            $this->jobTrackingService
        );

        // Mock the possibleRoles method by creating a partial mock
        /** @var AdminService&\Mockery\MockInterface */
        $adminService = Mockery::mock(AdminService::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $adminService->shouldReceive('possibleRoles')
            ->once()
            ->andReturn($mockRoles);

        // Act
        $result = $adminService->edit($admin);

        // Assert
        $this->assertEquals([
            'admin' => $admin,
            'roles' => $mockRoles
        ], $result);
    }

    // ==================== UPDATE METHOD TESTS ====================

    public function test_update_admin_success()
    {
        // Arrange
        $admin = $this->admin_partial;

        $data = [
            'name' => 'Updated Admin',
            'email' => 'updated@example.com',
            'role' => [2, 3],
        ];

        // Create a mock of the actual BelongsToMany relationship
        $mockRolesRelation = Mockery::mock(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
        $mockRolesRelation->shouldReceive('sync')
            ->with([2, 3])
            ->once()
            ->andReturn(['attached' => [2, 3], 'detached' => [], 'updated' => []]);
        
        $admin->shouldReceive('roles')
            ->once()
            ->andReturn($mockRolesRelation);

        $this->adminRepository
            ->shouldReceive('update')
            ->with($admin, [
                'name' => $data['name'],
                'email' => $data['email'],
            ])
            ->once()
            ->andReturn($admin);

        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) { 
                return $callback(); 
            });

        $this->flasher
            ->shouldReceive('notifyCrudResult')
            ->with(true, 'updated')
            ->once();

        // Act
        $result = $this->adminService->update($admin, $data);

        // Assert
        $this->assertTrue($result);
    }

    public function test_update_admin_failure()
    {
        // Arrange
        $admin = $this->admin_partial;
        $data = [
            'name' => 'Updated Admin',
            'email' => 'updated@example.com',
            'role' => [2, 3],
        ];

        $this->adminRepository
            ->shouldReceive('update')
            ->with($admin, [
                'name' => $data['name'],
                'email' => $data['email'],
            ])
            ->once()
            ->andThrow(new Exception('Update failed'));

        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) { 
                return $callback(); 
            });

        $this->flasher
            ->shouldReceive('notifyCrudResult')
            ->with(false, 'updated')
            ->once();

        // Act
        $result = $this->adminService->update($admin, $data);

        // Assert
        $this->assertFalse($result);
    }

    // ==================== DELETE METHOD TESTS ====================

    public function test_delete_admin_success()
    {
        Bus::fake();
        // Arrange
        $admin = $this->admin_partial;
        $trackingId = 'tracking-123';

        // Fake the job with skipTrackingCreation = true
        app()->bind(SafeDeleteAuditorJob::class, function () use ($admin) {
            return new SafeDeleteAuditorJob(
                auditor: $admin,
                userId: 1, // or Auth::id()
                skipTrackingCreation: true
            );
        });

        $this->jobTrackingService
            ->shouldReceive('dispatchWithTracking')
            ->once()
            ->andReturn($trackingId);

        $this->adminRepository
            ->shouldReceive('delete')
            ->with($admin)
            ->once();

        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) { 
                return $callback(); 
            });

        $this->flasher
            ->shouldReceive('notifyCrudResult')
            ->with(true, 'deleted')
            ->once();

        // Act
        $result = $this->adminService->delete($admin);

        // Assert
        $this->assertTrue($result);
        
        // Assert job was dispatched
        Bus::assertDispatched(SafeDeleteAuditorJob::class, function ($job) use ($admin) {
            return $job->getPayloadData()['auditor_id'] === $admin->id;
        });
    }

    public function test_delete_admin_failure()
    {
        // Arrange
        $admin = $this->admin_partial;

        $this->adminRepository
            ->shouldReceive('delete')
            ->with($admin)
            ->once()
            ->andThrow(new Exception('Delete failed'));

        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) { 
                return $callback(); 
            });

        $this->flasher
            ->shouldReceive('notifyCrudResult')
            ->with(false, 'deleted')
            ->once();

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Admin deleting error:') && 
                       $context['exception'] === 'Exception';
            });

        // Act
        $result = $this->adminService->delete($admin);

        // Assert
        $this->assertFalse($result);
    }

    public function test_delete_admin_transaction_rollback_on_exception()
    {
        // Arrange
        $admin = $this->admin_partial;

        $this->adminRepository
            ->shouldReceive('delete')
            ->with($admin)
            ->once()
            ->andThrow(new Exception('Delete failed'));

        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) { 
                return $callback(); 
            });

        $this->flasher
            ->shouldReceive('notifyCrudResult')
            ->with(false, 'deleted')
            ->once();

        Log::shouldReceive('error')
            ->once();

        // Act
        $result = $this->adminService->delete($admin);

        // Assert
        $this->assertFalse($result);
    }

    public function test_delete_admin_verifies_job_dispatching()
    {
        // Arrange
        $admin = $this->admin_partial;
        $trackingId = 'tracking-456';

        $this->jobTrackingService
            ->shouldReceive('dispatchWithTracking')
            ->once()
            ->andReturn($trackingId);

        $this->adminRepository
            ->shouldReceive('delete')
            ->with($admin)
            ->once();

        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) { 
                return $callback(); 
            });

        $this->flasher
            ->shouldReceive('notifyCrudResult')
            ->with(true, 'deleted')
            ->once();

        // Act
        $result = $this->adminService->delete($admin);

        // Assert
        $this->assertTrue($result);
        
        // Verify job was dispatched with correct parameters
        Queue::assertPushed(SafeDeleteAuditorJob::class, function ($job) use ($admin) {
            return $job->auditor->id === $admin->id && 
                   $job->userId === 1; // Auth::id() should return 1
        });
    }

    // ==================== EDGE CASES AND ERROR SCENARIOS ====================

    public function test_create_admin_with_empty_data()
    {
        // Arrange
        $data = [];

        $this->adminRepository
            ->shouldReceive('create')
            ->with($data)
            ->once()
            ->andThrow(new Exception('Validation failed'));

        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) { 
                return $callback(); 
            });

        $this->flasher
            ->shouldReceive('notifyCrudResult')
            ->with(false, 'saved')
            ->once();

        Log::shouldReceive('error')
            ->once();

        // Act
        $result = $this->adminService->create($data);

        // Assert
        $this->assertFalse($result);
    }

    public function test_update_admin_with_partial_data()
    {
        // Arrange
        $admin = $this->admin_partial;
        $data = [
            'name' => 'Updated Admin',
            // Missing email and role
        ];

        $this->adminRepository
            ->shouldReceive('update')
            ->with($admin, [
                'name' => $data['name'],
                'email' => null, // Should be null since not provided
            ])
            ->once()
            ->andReturn($admin);

        $admin->shouldReceive('roles->sync')
            ->with(null) // Should be null since not provided
            ->once();

        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) { 
                return $callback(); 
            });

        $this->flasher
            ->shouldReceive('notifyCrudResult')
            ->with(true, 'updated')
            ->once();

        // Act
        $result = $this->adminService->update($admin, $data);

        // Assert
        $this->assertTrue($result);
    }

    public function test_delete_admin_with_job_tracking_failure()
    {
        // Arrange
        $admin = $this->admin_partial;

        $this->jobTrackingService
            ->shouldReceive('dispatchWithTracking')
            ->once()
            ->andThrow(new Exception('Job tracking failed'));

        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) { 
                return $callback(); 
            });

        $this->flasher
            ->shouldReceive('notifyCrudResult')
            ->with(false, 'deleted')
            ->once();

        Log::shouldReceive('error')
            ->once();

        // Act
        $result = $this->adminService->delete($admin);

        // Assert
        $this->assertFalse($result);
    }

    // ==================== TRAIT METHOD TESTING ====================

    public function test_possible_roles_trait_method_integration()
    {
        // Arrange
        $roles = collect([
            new Role(['id' => 1, 'name' => 'admin']),
            new Role(['id' => 2, 'name' => 'moderator']),
        ]);

        // Create a new AdminService instance with mocked dependencies
        $adminService = new AdminService(
            $this->adminRepository,
            $this->transactionManager,
            $this->flasher,
            $this->jobTrackingService
        );

        // Mock the possibleRoles method by creating a partial mock
        $adminService = Mockery::mock(AdminService::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $adminService->shouldReceive('possibleRoles')
            ->once()
            ->andReturn($roles);

        // Act
        $result = $adminService->all();

        // Assert
        $this->assertEquals(['roles' => $roles], $result);
    }

    // ==================== FLASH NOTIFICATION TESTS ====================

    public function test_flash_notifications_are_called_on_success()
    {
        // Arrange
        $data = $this->createAdminData();
        $admin = $this->admin_partial;

        $this->adminRepository
            ->shouldReceive('create')
            ->with($data)
            ->once()
            ->andReturn($admin);

        $admin->shouldReceive('roles->sync')
            ->with($data['role'])
            ->once();

        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) { 
                return $callback(); 
            });

        $this->flasher
            ->shouldReceive('notifyCrudResult')
            ->with(true, 'saved')
            ->once();

        // Act
        $result = $this->adminService->create($data);

        // Assert
        $this->assertTrue($result);
    }

    public function test_flash_notifications_are_called_on_failure()
    {
        // Arrange
        $data = $this->createAdminData();

        $this->adminRepository
            ->shouldReceive('create')
            ->with($data)
            ->once()
            ->andThrow(new Exception('Database error'));

        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) { 
                return $callback(); 
            });

        $this->flasher
            ->shouldReceive('notifyCrudResult')
            ->with(false, 'saved')
            ->once();

        Log::shouldReceive('error')
            ->once();

        // Act
        $result = $this->adminService->create($data);

        // Assert
        $this->assertFalse($result);
    }
} 