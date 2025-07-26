<?php

namespace Tests\Feature\Services\Admin;

use Tests\TestCase;
use App\Models\Admin\Admin;
use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Services\Admin\AdminService;
use Bus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Database\Seeders\RoleSeeder;

class AdminServiceTest extends TestCase
{
    use RefreshDatabase;
    protected $service;
    protected $transactionManager;
    protected $flasher;
    protected $jobTrackingService;
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->transactionManager = Mockery::mock(\App\Contracts\TransactionManagerInterface::class);
        $this->flasher = Mockery::mock(\App\Contracts\FlasherInterface::class);
        $this->jobTrackingService = Mockery::mock(\App\Services\Monitoring\JobTrackingService::class);
        $this->service = new AdminService($this->transactionManager, $this->flasher, $this->jobTrackingService);
        $this->admin = Admin::factory()->create();
        Auth::shouldReceive('id')->andReturn($this->admin->id);

    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_index_returns_correct_counts()
    {
        Admin::factory()->count(3)->create();
        User::factory()->count(5)->create();


        $result = $this->service->index();
        $this->assertEquals(['count' => ['admin' => 4, 'user' => 5]], $result);
    }

    public function test_adminList_returns_possible_roles_for_super_admin_and_normal()
    {
        $superAdmin = Admin::factory()->create();
        $superAdmin->assignRole('super_admin');
        Auth::shouldReceive('user')->andReturn($superAdmin);


        $result = $this->service->adminList();
        $this->assertArrayHasKey('roles', $result);
        $this->assertNotEmpty($result['roles']);

        // Test for normal admin
        $admin = Admin::factory()->create();
        $admin->assignRole('manager');;
        Auth::shouldReceive('user')->andReturn($admin);
        $result = $this->service->adminList();
        $this->assertArrayHasKey('roles', $result);
        $this->assertNotEmpty($result['roles']);
    }

    public function test_create_admin_success()
    {
        $role = Role::where('name', 'manager')->first();
        $adminData = [
            'name' => 'Test Admin',
            'email' => 'test@example.com',
            'password' => 'password123',
            'birthdate' => '1990-01-01',
            'gender' => 'male',
            'role' => [$role->id],
        ];
        $this->transactionManager->shouldReceive('run')->andReturnUsing(fn($cb) => $cb());
        $this->flasher->shouldReceive('crudSuccess')->with('saved')->once();

        $result = $this->service->create($adminData);

        $this->assertTrue($result);
        $this->assertDatabaseHas('admins', [
            'name' => 'Test Admin',
            'email' => 'test@example.com',
        ]);
        $admin = Admin::where('email', 'test@example.com')->first();
        $this->assertTrue($admin->roles->contains('id', $role->id));
    }

    public function test_create_admin_handles_exception_and_logs()
    {
        $role = Role::where('name', 'manager')->first();
        $adminData = [
            'name' => 'Test Admin',
            'email' => 'test2@example.com',
            'password' => 'password123',
            'birthdate' => '1990-01-01',
            'gender' => 'male',
            'role' => [$role->id],
        ];
        $this->transactionManager->shouldReceive('run')->andThrow(new \Exception('DB error'));
        $this->flasher->shouldReceive('crudFailure')->with('saved')->once();

        $result = $this->service->create($adminData);

        $this->assertFalse($result);
    }

    public function test_edit_returns_admin_and_roles()
    {
        $admin = Admin::factory()->create();
        $admin->assignRole('super_admin');
        Auth::shouldReceive('user')->andReturn($admin);

        $result = $this->service->edit($admin);

        $this->assertEquals($admin->id, $result['admin']->id);
        $this->assertArrayHasKey('roles', $result);
        $this->assertNotEmpty($result['roles']);
    }

    public function test_update_admin_success()
    {
        $admin = Admin::factory()->create();
        $role = Role::where('name', 'manager')->first();
        $admin->assignRole('super_admin');
        $data = [
            'name' => 'Updated Admin',
            'email' => 'updated@example.com',
            'role' => $role->id,
        ];

        $this->transactionManager->shouldReceive('run')->andReturnUsing(fn($cb) => $cb());
        $this->flasher->shouldReceive('crudSuccess')->with('updated')->once();

        $result = $this->service->update($admin, $data);

        $this->assertTrue($result);
        $this->assertDatabaseHas('admins', [
            'name' => 'Updated Admin',
            'email' => 'updated@example.com',
        ]);
        $this->assertTrue($admin->fresh()->roles->contains('id', $role->id));
    }

    public function test_update_admin_handles_exception_and_logs()
    {
        $admin = Admin::factory()->create();
        $role = Role::where('name', 'manager')->first();
        $admin->assignRole('super_admin');
        $data = [
            'name' => 'Updated Admin',
            'email' => 'updated@example.com',
            'role' => [$role->id],
        ];
        $this->transactionManager->shouldReceive('run')->andThrow(new \Exception('DB error'));
        $this->flasher->shouldReceive('crudFailure')->with('updated')->once();

        
        $result = $this->service->update($admin, $data);

        $this->assertFalse($result);
    }

    public function test_delete_admin_success()
    {
        $admin = Admin::factory()->create();
        $admin->assignRole('super_admin');
        $this->transactionManager->shouldReceive('run')->andReturnUsing(fn($cb) => $cb());
        $this->flasher->shouldReceive('info')->withAnyArgs()->once();
        $this->jobTrackingService->shouldReceive('dispatchWithTracking')->once();

        $result = $this->service->delete($admin, 'test reason');

        $this->assertTrue($result);
    }

    public function test_delete_admin_handles_exception_and_logs()
    {
        $admin = Admin::factory()->create();
        $admin->assignRole('super_admin');
        $this->transactionManager->shouldReceive('run')->andThrow(new \Exception('DB error'));
        $this->flasher->shouldReceive('info')->withAnyArgs()->once();

        $result = $this->service->delete($admin, 'test reason');

        $this->assertFalse($result);
    }

    public function test_createDeleteJob_creates_job_with_correct_arguments()
    {
        Bus::fake();
        $admin = Admin::factory()->make(['id' => 123]);
        
        $service = new AdminService($this->transactionManager, $this->flasher, $this->jobTrackingService);
        $job = (new \ReflectionClass($service))->getMethod('createDeleteJob');
        $job->setAccessible(true);
        $result = $job->invoke($service, $admin, 'reason for delete');
        
        $this->assertInstanceOf(\App\Jobs\Admin\DeleteAdminCoordinatorJob::class, $result);
        $this->assertEquals($admin->id, $result->getAdmin()->id);
        $this->assertEquals('reason for delete', $result->getReason());
        $this->assertEquals(1, $result->getInitiatorId());
    }
} 