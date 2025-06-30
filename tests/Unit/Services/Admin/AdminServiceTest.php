<?php

namespace Tests\Unit\Services\Admin;

use Bus;
use Mockery;
use Exception;
use Tests\TestCase;
use App\Models\Admin\Admin;
use Spatie\Permission\Models\Role;
use App\Contracts\FlasherInterface;
use Illuminate\Support\Facades\Log;
use App\Services\Admin\AdminService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use App\Contracts\TransactionManagerInterface;
use App\Jobs\Competition\SafeDeleteAuditorJob;
use App\Services\Monitoring\JobTrackingService;
use App\Interface\Admin\AdminRepositoryInterface;
use App\Interface\Monitoring\JobTrackingStrategyInterface;
use App\Repository\Monitoring\InMemoryJobTrackingStrategy;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

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
    /** @var JobTrackingStrategyInterface&\Mockery\MockInterface */
    protected $jobTrackingStrategy;
    /** @var Admin|\Mockery\MockInterface */
    protected $admin_partial;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(JobTrackingStrategyInterface::class, InMemoryJobTrackingStrategy::class);
        
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
        $adminData = $this->createAdminData(['role' => 1]);
        $expectedCreateData = array_merge($adminData, ['admin_id' => 1]);
        
        $mockAdmin = $this->admin_partial;
        
        // Create a mock of the actual BelongsToMany relationship
        $mockRolesRelation = Mockery::mock(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
        $mockRolesRelation->shouldReceive('sync')
            ->with(1)
            ->once()
            ->andReturn(['attached' => [1], 'detached' => [], 'updated' => []]);
        
        $mockAdmin->shouldReceive('roles')
            ->once()
            ->andReturn($mockRolesRelation);

        $this->adminRepository
            ->shouldReceive('create')
            ->with($expectedCreateData)
            ->once()
            ->andReturn($mockAdmin);

        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) { 
                return $callback(); 
            });

        $this->flasher
            ->shouldReceive('crudSuccess')
            ->with('saved')
            ->once();

        // Act
        $result = $this->adminService->create($adminData);

        // Assert
        $this->assertTrue($result);
    }

    public function test_create_admin_failure()
    {
        // Arrange
        $adminData = $this->createAdminData(['role' => [1, 2]]);
        $expectedCreateData = array_merge($adminData, ['admin_id' => 1]);

        $this->adminRepository
            ->shouldReceive('create')
            ->with($expectedCreateData)
            ->once()
            ->andThrow(new Exception('Database error'));

        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) { 
                return $callback(); 
            });

        $this->flasher
            ->shouldReceive('crudFailure')
            ->with('saved')
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
            $this->jobTrackingService,
            $this->jobTrackingStrategy
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
            ->shouldReceive('crudSuccess')
            ->with('updated')
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
            ->shouldReceive('crudFailure')
            ->with('updated')
            ->once();

        // Act
        $result = $this->adminService->update($admin, $data);

        // Assert
        $this->assertFalse($result);
    }

    // ==================== DELETE METHOD TESTS ====================

    public function test_delete_admin_successfully()
    {
        Bus::fake();
        
        // Arrange
        $admin = $this->admin_partial;
        
        $this->adminRepository
            ->shouldReceive('delete')
            ->once()
            ->with($admin)
            ->andReturn(true);

        $this->jobTrackingService
            ->shouldReceive('dispatchWithTracking')
            ->once()
            ->andReturn('tracking-id');

        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(fn($callback) => $callback());

        $this->flasher
            ->shouldReceive('info')
            ->with('deleted')
            ->once();

        // Act
        $result = $this->adminService->delete($admin);

        // Assert
        $this->assertTrue($result);
        
    }

    public function test_delete_admin_handles_job_failure_gracefully()
    {
        // Arrange
        $admin = $this->admin_partial;
        
        $this->jobTrackingService
            ->shouldReceive('dispatchWithTracking')
            ->once()
            ->andThrow(new Exception('Job failed'));

        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(fn($callback) => $callback());

        $this->flasher
            ->shouldReceive('crudFailure')
            ->with('deleted')
            ->once();

        // Act
        $result = $this->adminService->delete($admin);

        // Assert
        $this->assertFalse($result);
    }

} 