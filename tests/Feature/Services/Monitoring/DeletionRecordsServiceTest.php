<?php

namespace Tests\Unit\Services;

use Bus;
use Mockery;
use Tests\TestCase;
use App\Models\User;
use App\Models\Admin\Admin;
use Illuminate\Http\Request;
use App\Contracts\FlasherInterface;
use App\Jobs\Admin\RestoreAdminJob;
use App\Models\Competition\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\Competition\Competition;
use App\Models\Monitoring\DeletionRequest;
use App\Contracts\TransactionManagerInterface;
use App\Services\Monitoring\JobTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Factories\Monitoring\RestoreHandlerFactory;
use App\Services\Monitoring\DeletionRecordsService;
use App\Factories\Monitoring\HardDeleteHandlerFactory;
use App\Interface\Monitoring\DeletionRequests\HardDeleteHandlerInterface;

class DeletionRecordsServiceTest extends TestCase
{
    use RefreshDatabase;

    private $transactionManager;
    private $jobTrackingService;
    private $flasher;
    private $restoreHandlerFactory;
    private $hardDeleteHandlerFactory;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Run the role seeder to ensure roles exist
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->transactionManager = \Mockery::mock(TransactionManagerInterface::class);
        $this->jobTrackingService = \Mockery::mock(JobTrackingService::class);
        $this->flasher = \Mockery::mock(FlasherInterface::class);
        $this->restoreHandlerFactory = \Mockery::mock(RestoreHandlerFactory::class);
        $this->hardDeleteHandlerFactory = Mockery::mock(HardDeleteHandlerFactory::class); // Use real factory for integration
        $this->service = new DeletionRecordsService(
            $this->transactionManager,
            $this->jobTrackingService,
            $this->flasher,
            $this->restoreHandlerFactory,
            $this->hardDeleteHandlerFactory
        );
    }

    public function test_hard_delete_admin_process_performs_all_expected_updates()
    {
        // Arrange
        Bus::fake();
        $user = User::factory()->make(['id' => 1]);
        $admin = Admin::factory()->make(['id' => 1]);
        $deletionRequest = Mockery::mock(DeletionRequest::class);
        $deletionRequest->shouldReceive('getAttribute')->with('deletable')->andReturn($user);
        
        $handler = Mockery::mock(HardDeleteHandlerInterface::class);
        $handler->shouldReceive('delete')->once()->andReturn(true);

        $this->hardDeleteHandlerFactory
            ->shouldReceive('make')
            ->once()
            ->with($user, $deletionRequest, $admin, null)
            ->andReturn($handler);

        // Mock Auth::user()
        $this->actingAs($admin, 'admin');

        // Act
        $result = $this->service->hardDelete($deletionRequest, null);

        // Assert
        $this->assertTrue($result);
    }

    public function test_restore_user_successfully_restores_soft_deleted_user()
    {
        // Arrange
        $user = User::factory()->create();
        $user->delete();
        $admin = Admin::factory()->create();
        $deletionRequest = DeletionRequest::factory()->create([
            'deletable_type' => User::class,
            'deletable_id' => $user->id,
        ]);
        $this->actingAs($admin, 'admin');

        // Use real restore handler factory
        $restoreHandlerFactory = app(\App\Factories\Monitoring\RestoreHandlerFactory::class);
        $service = new DeletionRecordsService(
            $this->transactionManager,
            $this->jobTrackingService,
            $this->flasher,
            $restoreHandlerFactory,
            $this->hardDeleteHandlerFactory
        );

        // Act
        $result = $service->restore($deletionRequest);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'deleted_at' => null,
        ]);
    }

    public function test_restore_admin_dispatches_restore_job()
    {
        // Arrange
        Bus::fake();
        $admin = Admin::factory()->create(['deleted_at' => now()]);
        $initiator = Admin::factory()->create();
        $deletionRequest = DeletionRequest::factory()->create([
            'deletable_type' => Admin::class,
            'deletable_id' => $admin->id,
        ]);
        $this->actingAs($initiator, 'admin');

        $restoreHandlerFactory = app(\App\Factories\Monitoring\RestoreHandlerFactory::class);
        $service = new DeletionRecordsService(
            $this->transactionManager,
            $this->jobTrackingService,
            $this->flasher,
            $restoreHandlerFactory,
            $this->hardDeleteHandlerFactory
        );

        // Act
        $result = $service->restore($deletionRequest);

        // Assert
        $this->assertTrue($result);
        Bus::assertDispatched(RestoreAdminJob::class);
    }

    public function test_restore_returns_false_and_logs_on_exception()
    {
        // Arrange
        $user = User::factory()->create();
        $user->delete();
        $admin = Admin::factory()->create();
        $deletionRequest = DeletionRequest::factory()->create([
            'deletable_type' => User::class,
            'deletable_id' => $user->id,
        ]);
        $this->actingAs($admin, 'admin');

        // Use a partial mock to throw exception
        $restoreHandlerFactory = \Mockery::mock(RestoreHandlerFactory::class);
        $restoreHandlerFactory->shouldReceive('make')
            ->once()
            ->andThrow(new \Exception('Handler error'));
        $service = new DeletionRecordsService(
            $this->transactionManager,
            $this->jobTrackingService,
            $this->flasher,
            $restoreHandlerFactory,
            $this->hardDeleteHandlerFactory
        );

        // Act
        $result = $service->restore($deletionRequest);

        // Assert
        $this->assertFalse($result);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
} 