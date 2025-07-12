<?php

namespace Tests\Unit\Services;

use App\Models\Admin\Admin;
use App\Models\User;
use App\Models\Competition\Competition;
use App\Models\Competition\Response;
use App\Models\Monitoring\DeletionRequest;
use App\Services\Monitoring\DeletionRecordsService;
use App\Contracts\FlasherInterface;
use App\Factories\Monitoring\RestoreHandlerFactory;
use App\Factories\Monitoring\HardDeleteHandlerFactory;
use App\Services\Monitoring\JobTrackingService;
use App\Contracts\TransactionManagerInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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
        $this->hardDeleteHandlerFactory = app(HardDeleteHandlerFactory::class); // Use real factory for integration
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
        // Create a transfer admin with the 'owner' role
        $transferAdmin = Admin::factory()->create();
        $transferAdmin->assignRole('owner');
        // Create a soft-deleted admin to delete
        $adminToDelete = Admin::factory()->create(['deleted_at' => now()]);
        Auth::shouldReceive('user')->andReturn($transferAdmin);
        Auth::shouldReceive('id')->andReturn($transferAdmin->id);

        // Suspended competitions owned by adminToDelete
        $suspendedCompetitions = Competition::factory()->count(2)->create([
            'admin_id' => $adminToDelete->id,
            'is_suspended' => true,
        ]);
        // Finished competitions owned by adminToDelete
        $finishedCompetitions = Competition::factory()->count(2)->create([
            'admin_id' => $adminToDelete->id,
            'status' => Competition::STATUS_COMPLETED,
            'is_suspended' => false,
        ]);
        // Users created by adminToDelete
        $users = User::factory()->count(2)->create([
            'admin_id' => $adminToDelete->id,
        ]);
        // Admins created by adminToDelete
        $admins = Admin::factory()->count(2)->create([
            'admin_id' => $adminToDelete->id,
        ]);
        // Responses audited by adminToDelete, with full relationship chain
        $responses = collect();
        foreach ([$suspendedCompetitions, $finishedCompetitions] as $competitionSet) {
            foreach ($competitionSet as $competition) {
                $level = \App\Models\Competition\Level::factory()
                    ->withCompetition($competition)
                    ->create([
                        'admin_id' => $adminToDelete->id,
                        'status' => 'pending', // or 'active' or 'finished'
                    ]);
                $question = \App\Models\Competition\Question::factory()->withLevel($level)->create();
                $user = User::factory()->create();
                $response = Response::factory()
                    ->withQuestion($question)
                    ->withUser($user)
                    ->withAdmin($adminToDelete)
                    ->create();
                $responses->push($response);
            }
        }

        // Create DeletionRequest for adminToDelete
        $deletionRequest = DeletionRequest::factory()->create([
            'deletable_id' => $adminToDelete->id,
            'deletable_type' => Admin::class,
        ]);
        // Attach the deletable relation for the service
        $deletionRequest->setRelation('deletable', $adminToDelete);

        // Prepare request with transfer admin
        $request = new Request(['admin_id' => $transferAdmin->id]);

        // Run the hard delete process
        $result = $this->service->hardDelete($deletionRequest, $request);
        $this->assertTrue($result);

        // Suspended competitions should be deleted
        foreach ($suspendedCompetitions as $competition) {
            $this->assertDatabaseMissing('competitions', ['id' => $competition->id]);
        }
        // Finished competitions should be transferred
        foreach ($finishedCompetitions as $competition) {
            $this->assertDatabaseHas('competitions', [
                'id' => $competition->id,
                'admin_id' => $transferAdmin->id,
            ]);
        }
        // Users should be transferred
        foreach ($users as $user) {
            $this->assertDatabaseHas('users', [
                'id' => $user->id,
                'admin_id' => $transferAdmin->id,
            ]);
        }
        // Admins should be transferred
        foreach ($admins as $admin) {
            $this->assertDatabaseHas('admins', [
                'id' => $admin->id,
                'admin_id' => $transferAdmin->id,
            ]);
        }
        // Responses should be transferred
        foreach ($responses as $response) {
            $this->assertDatabaseHas('responses', [
                'id' => $response->id,
                'admin_id' => $transferAdmin->id,
            ]);
        }
        // The deleted admin should be gone
        $this->assertDatabaseMissing('admins', [
            'id' => $adminToDelete->id,
        ]);
    }
} 