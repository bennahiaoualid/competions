<?php

namespace Tests\Unit;

use Bus;
use Mockery;
use Exception;
use Tests\TestCase;
use App\Models\Admin\Admin;
use App\Contracts\FlasherInterface;
use Illuminate\Support\Facades\Auth;
use App\Helpers\UserNotifyEmail;
use Illuminate\Support\Facades\Queue;
use App\Models\Competition\Competition;
use App\Jobs\Competition\DeleteAuditorJob;
use App\Contracts\TransactionManagerInterface;
use App\Services\Competition\CompetitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Jobs\Competetion\SyncCompetitionParticipants;
use App\Interface\Competition\CompetitionRepositoryInterface;

class CompetitionServiceTest extends TestCase
{
    /** @var CompetitionService&\Mockery\MockInterface */
    protected $competitionService;
    /** @var CompetitionRepositoryInterface&\Mockery\MockInterface */
    protected $competitionRepository;
    /** @var TransactionManagerInterface&\Mockery\MockInterface */
    protected $transactionManager;
    /** @var FlasherInterface&\Mockery\MockInterface */
    protected $flasher;
    /** @var Competition|\Mockery\MockInterface */
    protected $competition_partial;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mock repository & transaction manager & flasher
        $this->competitionRepository = Mockery::mock(CompetitionRepositoryInterface::class);
        $this->transactionManager = Mockery::mock(TransactionManagerInterface::class);
        $this->flasher = Mockery::mock(FlasherInterface::class);
        
        $this->competitionService = new CompetitionService($this->competitionRepository, $this->transactionManager, $this->flasher);

        $this->competition_partial = Mockery::mock(Competition::class)->makePartial();
        $this->competition_partial->id = 1;
        
        // Mock Auth facade
        Auth::shouldReceive('id')->andReturn(1);
        Auth::shouldReceive('user')->andReturn($this->mockAdmin('owner',true));
        
        // Fake queues for job testing
        Queue::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function createCompetitionData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Test Competition',
            'description' => 'Test Description',
            'start_date' => now()->addDay(),
            'age_start' => 10,
            'age_end' => 15,
            'levels_number' => 3,
            'status' => '0', // inactive
        ], $overrides);
    }

    public function test_create_competition_success()
    {
        // Arrange
        $data = $this->createCompetitionData();
        $competition = $this->competition_partial;
        foreach ($data as $key => $value) {
            $competition->$key = $value;
        }
    
    
        $this->competitionRepository
            ->shouldReceive('create')
            ->with($data)
            ->once()
            ->andReturn($competition);
        
        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) { return $callback(); });
    
        $this->flasher
            ->shouldReceive('notifyCrudResult')
            ->with(true, "saved")
            ->once();
    
        // Act
        $result = $this->competitionService->createCompetition($data);
        
        // Assert
        $this->assertTrue($result);
        
        // Assert job was dispatched with correct parameters
        Queue::assertPushed(SyncCompetitionParticipants::class, function ($job) use ($competition) {
            return $job->competition->id === $competition->id && $job->isUpdate === false;
        });
    }

    public function test_create_competition_failure()
    {
        // Arrange
        $data = $this->createCompetitionData();
        $competition = $this->competition_partial;
        foreach ($data as $key => $value) {
            $competition->$key = $value;
        }

        $this->competitionRepository
            ->shouldReceive('create')
            ->with($data)
            ->once()
            ->andThrow(new Exception('Database error'));

        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) { return $callback(); });

        $this->flasher
            ->shouldReceive('notifyCrudResult')
            ->with(false, "saved")
            ->once();

        // Act
        $result = $this->competitionService->createCompetition($data);
        
        // Assert
        $this->assertFalse($result);
        Queue::assertNothingPushed();
    }

    public function test_update_competition_success_with_resync()
    {
        // Arrange
        $data = $this->createCompetitionData();
        $new_data = [
            'description' => 'new Description',
            'age_start' => 12, // This should trigger resync
            'age_end' => 15,
        ];
        $competition = $this->competition_partial;
        foreach ($data as $key => $value) {
            $competition->$key = $value;
        }
        $competition->shouldReceive('fill')->with($new_data)->andReturnSelf();

        $this->competitionRepository
            ->shouldReceive('update')
            ->with($competition, $new_data)
            ->once()
            ->andReturn([
                'competition' => $competition, 
                'resyncCompetitionParticipants' => true
            ]);
        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) { return $callback(); });     
        
        $this->flasher
            ->shouldReceive('notifyCrudResult')
            ->with(true, "updated")
            ->once();

        // Act
        $result = $this->competitionService->updateCompetition($competition, $new_data);
        
        // Assert
        $this->assertTrue($result);
        Queue::assertPushed(SyncCompetitionParticipants::class, function ($job) use ($competition) {
            return $job->competition->id === $competition->id && $job->isUpdate === true;
        });
    }

    public function test_update_competition_success_without_resync()
    {
        // Arrange
        $data = $this->createCompetitionData();
        $new_data = [
            'description' => 'new Description',
            'age_start' => 12,
            'age_end' => 15,
        ];
        $competition = $this->competition_partial;
        foreach ($data as $key => $value) {
            $competition->$key = $value;
        }
        $competition->shouldReceive('fill')->with($new_data)->andReturnSelf();

        $this->competitionRepository
            ->shouldReceive('update')
            ->with($competition, $new_data)
            ->once()
            ->andReturn([
                'competition' => $competition, 
                'resyncCompetitionParticipants' => false
            ]);
        
        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) { return $callback(); });

        $this->flasher
            ->shouldReceive('notifyCrudResult')
            ->with(true, "updated")
            ->once();

        // Mock UserNotifyEmail
        $userNotifyEmail = Mockery::mock('alias:' . UserNotifyEmail::class);
        $userNotifyEmail->shouldReceive('usersUpdateCompetition')
            ->with($competition)
            ->once();

        // Act
        $result = $this->competitionService->updateCompetition($competition, $new_data);
        
        // Assert
        $this->assertTrue($result);
        Queue::assertNothingPushed();
    }

    public function test_update_competition_failure()
    {
        // Arrange
        $data = $this->createCompetitionData();
        $new_data = [
            'description' => 'new Description',
        ];
        $competition = $this->competition_partial;
        foreach ($data as $key => $value) {
            $competition->$key = $value;
        }
        $competition->shouldReceive('fill')->with($new_data)->andReturnSelf();

        $this->competitionRepository
            ->shouldReceive('update')
            ->with($competition, $new_data)
            ->once()
            ->andThrow(new Exception('Database error'));

        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) { return $callback(); });

        $this->flasher
            ->shouldReceive('notifyCrudResult')
            ->with(false, "updated")
            ->once();

        // Act
        $result = $this->competitionService->updateCompetition($competition, $new_data);
        
        // Assert
        $this->assertFalse($result);
        Queue::assertNothingPushed();
    }

    public function test_delete_competition_success()
    {
        // Arrange
        $data = $this->createCompetitionData();
        $competition = $this->competition_partial;
        foreach ($data as $key => $value) {
            $competition->$key = $value;
        }
        $competition->shouldReceive('canEdit')->andReturn(true);
        
        // Mock Auth user with owner role
        $admin = $this->mockAdmin('owner',true);
        Auth::shouldReceive('user')->andReturn($admin);
        
        
        $this->competitionRepository
            ->shouldReceive('findById')
            ->with($competition->id)
            ->once()
            ->andReturn($competition);
            
        $this->competitionRepository
            ->shouldReceive('delete')
            ->with($competition)
            ->once();

        $this->flasher
            ->shouldReceive('notifyCrudResult')
            ->with(true, "deleted")
            ->once();
        
        // Act
        $result = $this->competitionService->deleteCompetition($competition->id);
        
        // Assert
        $this->assertTrue($result);
    }

    public function test_delete_competition_not_found()
    {
        // Arrange
        $competitionId = '100000000';
        
        $this->competitionRepository
            ->shouldReceive('findById')
            ->with($competitionId)
            ->once()
            ->andReturn(null);

        $this->flasher
            ->shouldReceive('notify')
            ->with('Competition not found.', 'error')
            ->once();
        
        // Act
        $result = $this->competitionService->deleteCompetition($competitionId);
        
        // Assert
        $this->assertFalse($result);

    }

    public function test_delete_competition_unauthorized()
    {
        // Arrange
        $data = $this->createCompetitionData();

        $competition = $this->competition_partial;
        foreach ($data as $key => $value) {
            $competition->$key = $value;
        }

        // Mock Auth user without owner role
        $admin = $this->mockAdmin('owner',false);
        Auth::shouldReceive('user')->andReturn($admin);
        
        //  override canEdit method
        $competition->shouldReceive('canEdit')->andReturn(false);
        
        $this->competitionRepository
            ->shouldReceive('findById')
            ->with($competition->id)
            ->once()
            ->andReturn($competition);
        
        $this->flasher
            ->shouldReceive('notify')
            ->with(trans('messages.validation.not_allow.competition_delete'), 'error')
            ->once();

        // Act
        $result = $this->competitionService->deleteCompetition($competition->id);
        
        // Assert
        $this->assertFalse($result);
    }

    public function test_delete_competition_failure()
    {
        // Arrange
        $data = $this->createCompetitionData();

        $competition = $this->competition_partial;
        foreach ($data as $key => $value) {
            $competition->$key = $value;
        }
        $competition->shouldReceive('canEdit')->andReturn(true);
        
        // Mock Auth user with owner role
        $admin = $this->mockAdmin('owner',true);
        Auth::shouldReceive('user')->andReturn($admin);

        
        $this->competitionRepository
            ->shouldReceive('findById')
            ->with($competition->id)
            ->once()
            ->andReturn($competition);
            
        $this->competitionRepository
            ->shouldReceive('delete')
            ->with($competition)
            ->once()
            ->andThrow(new Exception('Database error'));

        $this->flasher
            ->shouldReceive('notifyCrudResult')
            ->with(false, "deleted")
            ->once();
        
        // Act
        $result = $this->competitionService->deleteCompetition($competition->id);
        
        // Assert
        $this->assertFalse($result);
    }

    public function test_add_competition_users_success()
    {
        // Arrange
        $data = $this->createCompetitionData();
        $competition = $this->competition_partial;
        foreach ($data as $key => $value) {
            $competition->$key = $value;
        }
        
        $user_ids = [1, 2, 3];
        
        $this->competitionRepository
            ->shouldReceive('addUsersToCompetition')
            ->with($competition, $user_ids)
            ->once();
        
        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) { return $callback(); });
        
        $this->flasher
            ->shouldReceive('notifyCrudResult')
            ->with(true, "saved")
            ->once();
        
        // Act
        $result = $this->competitionService->addCompetitionUsers($competition, $user_ids);
        
        // Assert
        $this->assertTrue($result);
    }

    public function test_add_competition_users_failure()
    {
        // Arrange
        $data = $this->createCompetitionData();
        $competition = $this->competition_partial;
        foreach ($data as $key => $value) {
            $competition->$key = $value;
        }
        
        $user_ids = [1, 2, 3];
        
        $this->competitionRepository
            ->shouldReceive('addUsersToCompetition')
            ->with($competition, $user_ids)
            ->once()
            ->andThrow(new Exception('Database error'));
        
        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) { return $callback(); });
        
        $this->flasher
            ->shouldReceive('notifyCrudResult')
            ->with(false, "saved")
            ->once();
        
        // Act
        $result = $this->competitionService->addCompetitionUsers($competition, $user_ids);
        
        // Assert
        $this->assertFalse($result);
    }

    public function test_remove_competition_user_success()
    {
        // Arrange
        $data = $this->createCompetitionData();
        $competition = $this->competition_partial;
        foreach ($data as $key => $value) {
            $competition->$key = $value;
        }
        $competition->shouldReceive('canEdit')->andReturn(true);

        $user_id = 1;
            
        $this->competitionRepository
            ->shouldReceive('removeUserFromCompetition')
            ->with($competition, $user_id)
            ->once();
        
        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) { return $callback(); });
        
        $this->flasher
            ->shouldReceive('notifyCrudResult')
            ->with(true, "deleted")
            ->once();
        
        // Act
        $result = $this->competitionService->removeCompetitionUser($competition, $user_id);
        
        // Assert
        $this->assertTrue($result);
    }

    public function test_remove_competition_user_failure()
    {
        // Arrange
        $data = $this->createCompetitionData();
        $competition = $this->competition_partial;
        foreach ($data as $key => $value) {
            $competition->$key = $value;
        }
        $competition->shouldReceive('canEdit')->andReturn(true);
        
        $user_id = 1;
            
        $this->competitionRepository
            ->shouldReceive('removeUserFromCompetition')
            ->with($competition, $user_id)
            ->once()
            ->andThrow(new Exception('Database error'));
        
        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) { return $callback(); });
        
        $this->flasher
            ->shouldReceive('notifyCrudResult')
            ->with(false, "deleted")
            ->once();
        
        // Act
        $result = $this->competitionService->removeCompetitionUser($competition, $user_id);
        
        // Assert
        $this->assertFalse($result);
    }

    public function test_add_competition_auditors_success()
    {
        // Arrange
        $data = $this->createCompetitionData();
        $competition = $this->competition_partial;
        foreach ($data as $key => $value) {
            $competition->$key = $value;
        }
        
        $competition->shouldReceive('canEdit')->andReturn(true);
        
        $auditor_ids = [1, 2, 3];
        
        $this->competitionRepository
            ->shouldReceive('addAuditorsToCompetition')
            ->with($competition, $auditor_ids)
            ->once();
        
        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) { return $callback(); });
        
        $this->flasher
            ->shouldReceive('notifyCrudResult')
            ->with(true, "saved")
            ->once();
        
        // Mock UserNotifyEmail
        $userNotifyEmail = Mockery::mock('alias:' . UserNotifyEmail::class);
        $userNotifyEmail->shouldReceive('auditorNewCompetition')
            ->with($competition, $auditor_ids)
            ->once();
        
        // Act
        $result = $this->competitionService->addCompetitionAuditors($competition, $auditor_ids);
        
        // Assert
        $this->assertTrue($result);
    }

    public function test_add_competition_auditors_unauthorized()
    {
        // Arrange
        $data = $this->createCompetitionData();
        $competition = $this->competition_partial;
        foreach ($data as $key => $value) {
            $competition->$key = $value;
        }
        
        $competition->shouldReceive('canEdit')->andReturn(false);
        
        $auditor_ids = [1, 2, 3];
        
        $this->flasher
            ->shouldReceive('notify')
            ->with(__('messages.validation.not_allow.competition_update'), 'error')
            ->once();
        
        // Act
        $result = $this->competitionService->addCompetitionAuditors($competition, $auditor_ids);
        
        // Assert
        $this->assertFalse($result);
    }

    public function test_add_competition_auditors_failure()
    {
        // Arrange
        $data = $this->createCompetitionData();
        $competition = $this->competition_partial;
        foreach ($data as $key => $value) {
            $competition->$key = $value;
        }
        
        $competition->shouldReceive('canEdit')->andReturn(true);
        
        $auditor_ids = [1, 2, 3];
        
        $this->competitionRepository
            ->shouldReceive('addAuditorsToCompetition')
            ->with($competition, $auditor_ids)
            ->once()
            ->andThrow(new Exception('Database error'));
        
        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) { return $callback(); });
        
        $this->flasher
            ->shouldReceive('notifyCrudResult')
            ->with(false, "saved")
            ->once();
        
        // Act
        $result = $this->competitionService->addCompetitionAuditors($competition, $auditor_ids);
        
        // Assert
        $this->assertFalse($result);
    }

    public function test_remove_competition_auditor_success()
    {
        // Arrange
        Bus::fake();
        $data = $this->createCompetitionData();
        $competition = $this->competition_partial;
        foreach ($data as $key => $value) {
            $competition->$key = $value;
        }
        
        $competition->shouldReceive('canEdit')->andReturn(true);
        // Mock auditors collection with count = 2
        $auditorsCollection = Mockery::mock();
        $auditorsCollection->shouldReceive('count')->andReturn(2);
        $competition->shouldReceive('getAttribute')->with('auditors')->andReturn($auditorsCollection);
        
        $auditor_id = 1;
        
        $this->flasher
            ->shouldReceive('notifyCrudResult')
            ->with(true, "deleted")
            ->once();
        
        // Act
        $result = $this->competitionService->removeCompetitionAuditor($competition, $auditor_id);
        
        // Assert
        $this->assertTrue($result);
        Bus::assertDispatched(DeleteAuditorJob::class, function ($job) use ($competition, $auditor_id) {
            return $job->getCompetition()->id === $competition->id
                && $job->getAuditorId() === $auditor_id;
        });
    }

    public function test_remove_competition_auditor_unauthorized()
    {
        Bus::fake();
        // Arrange
        $data = $this->createCompetitionData();
        $competition = $this->competition_partial;
        foreach ($data as $key => $value) {
            $competition->$key = $value;
        }
        
        $competition->shouldReceive('canEdit')->andReturn(false);
        
        $auditor_id = 1;
            
        $this->flasher
            ->shouldReceive('notify')
            ->with(__('messages.validation.not_allow.competition_update'), 'error')
            ->once();
        
        // Act
        $result = $this->competitionService->removeCompetitionAuditor($competition, $auditor_id);
        
        // Assert
        $this->assertFalse($result);
        Bus::assertNothingDispatched(DeleteAuditorJob::class);
    }

    public function test_remove_competition_auditor_last_auditor()
    {
        // Arrange
        Bus::fake();
        $data = $this->createCompetitionData();
        $competition = $this->competition_partial;
        foreach ($data as $key => $value) {
            $competition->$key = $value;
        }
        
        $competition->shouldReceive('canEdit')->andReturn(true);
        // Mock auditors collection with count = 1
        $auditorsCollection = Mockery::mock();
        $auditorsCollection->shouldReceive('count')->andReturn(1);
        $competition->shouldReceive('getAttribute')->with('auditors')->andReturn($auditorsCollection);
        
        $auditor_id = 1;
            
        $this->flasher
            ->shouldReceive('notify')
            ->with(__('messages.validation.not_allow.remove_auditor_only_one'), 'error')
            ->once();
        
        // Act
        $result = $this->competitionService->removeCompetitionAuditor($competition, $auditor_id);
        
        // Assert
        $this->assertFalse($result);
        Bus::assertNothingDispatched(DeleteAuditorJob::class);
    }

    public function test_activate_competition_success()
    {
        // Arrange

        // Mock all validations to pass
        $pastDate = now()->subDay();
        
        $levelsCollection = Mockery::mock();
        $levelsCollection->shouldReceive('count')->andReturn(3);
        
        $usersCollection = Mockery::mock();
        $usersCollection->shouldReceive('count')->andReturn(5);
        
        $auditorsCollection = Mockery::mock();
        $auditorsCollection->shouldReceive('count')->andReturn(2);

        $competition = $this->competition_partial;
        
        $competition->shouldReceive('getAttribute')->with('start_date')->andReturn($pastDate);
        $competition->shouldReceive('getAttribute')->with('levels')->andReturn($levelsCollection);
        $competition->shouldReceive('getAttribute')->with('levels_number')->andReturn(3);
        $competition->shouldReceive('getAttribute')->with('users')->andReturn($usersCollection);
        $competition->shouldReceive('getAttribute')->with('auditors')->andReturn($auditorsCollection);
        $competition->shouldReceive('isAllLevelAfterNow')->andReturn(true);
        
        // Mock setAttribute for start_date assignment
        $competition->shouldReceive('setAttribute')->with('start_date', Mockery::any())->andReturnSelf();
        
        $this->competitionRepository
            ->shouldReceive('activate')
            ->with($competition)
            ->once();
        
        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) { return $callback(); });
        
        // Mock UserNotifyEmail
        $userNotifyEmail = Mockery::mock('alias:' . UserNotifyEmail::class);
        $userNotifyEmail->shouldReceive('usersActivateCompetition')
            ->with($competition)
            ->once();
        
        $this->flasher
            ->shouldReceive('notifyCrudResult')
            ->with(true, "activated")
            ->once();
        
        // Act
        $result = $this->competitionService->activateCompetition($competition);
        
        // Assert
        $this->assertTrue($result);
    }

    public function test_activate_competition_early()
    {
        // Create a mock date that's in the future (should fail)
        $futureDate = now()->addDay();
        
        $competition = $this->competition_partial;

        // Mock the start_date attribute to return a Carbon instance
        $competition->shouldReceive('getAttribute')
            ->with('start_date')
            ->andReturn($futureDate);

        
        $this->flasher
            ->shouldReceive('notify')
            ->with(__('messages.validation.not_allow.competition_activate_early'), 'error')
            ->once();
        
        // Act
        $result = $this->competitionService->activateCompetition($competition);
        
        // Assert
        $this->assertFalse($result);
    }

    public function test_activate_competition_levels_mismatch()
    {

        // Arrange
        $competition = $this->competition_partial;
        $competition->levels_number = 3;

         // Create a mock date that past
        $pastDate = now()->subDay(); 
         // Mock the start_date attribute to return a Carbon instance
        $competition->shouldReceive('getAttribute')
            ->with('start_date')
            ->andReturn($pastDate);

        $levelsCollection = Mockery::mock();
        $levelsCollection->shouldReceive('count')->andReturn(2);
        $competition->shouldReceive('getAttribute')->with('levels')->andReturn($levelsCollection);
        
        $this->flasher
            ->shouldReceive('notify')
            ->with(__('messages.validation.not_allow.competition_activate_match_levels'), 'error')
            ->once();
        
        // Act
        $result = $this->competitionService->activateCompetition($competition);
        
        // Assert
        $this->assertFalse($result);
    }

    public function test_activate_competition_insufficient_competitors()
    {
        // Arrange
        $competition = $this->competition_partial;
        $competition->levels_number = 3;

            // Create a mock date that past
        $pastDate = now()->subDay(); 
            // Mock the start_date attribute to return a Carbon instance
        $competition->shouldReceive('getAttribute')
            ->with('start_date')
            ->andReturn($pastDate);

        $levelsCollection = Mockery::mock();
        $levelsCollection->shouldReceive('count')->andReturn(3);
        $competition->shouldReceive('getAttribute')->with('levels')->andReturn($levelsCollection);
        
        $usersCollection = Mockery::mock();
        $usersCollection->shouldReceive('count')->andReturn(2);
        $competition->shouldReceive('getAttribute')->with('users')->andReturn($usersCollection);

        $this->flasher
            ->shouldReceive('notify')
            ->with(__('messages.validation.not_allow.competition_activate_less_competitors'), 'error')
            ->once();
        
        // Act
        $result = $this->competitionService->activateCompetition($competition);
        
        // Assert
        $this->assertFalse($result);
    }

    public function test_activate_competition_no_auditors()
    {
        // Arrange
        $competition = $this->competition_partial;
        $competition->levels_number = 3;

            // Create a mock date that past
        $pastDate = now()->subDay(); 
            // Mock the start_date attribute to return a Carbon instance
        $competition->shouldReceive('getAttribute')
            ->with('start_date')
            ->andReturn($pastDate);

        $levelsCollection = Mockery::mock();
        $levelsCollection->shouldReceive('count')->andReturn(3);
        $competition->shouldReceive('getAttribute')->with('levels')->andReturn($levelsCollection);
        
        $usersCollection = Mockery::mock();
        $usersCollection->shouldReceive('count')->andReturn(3);
        $competition->shouldReceive('getAttribute')->with('users')->andReturn($usersCollection);
        
        $auditorsCollection = Mockery::mock();
        $auditorsCollection->shouldReceive('count')->andReturn(0);
        $competition->shouldReceive('getAttribute')->with('auditors')->andReturn($auditorsCollection);

        $this->flasher
            ->shouldReceive('notify')
            ->with(__('messages.validation.not_allow.competition_activate_less_auditor'), 'error')
            ->once();
        
        // Act
        $result = $this->competitionService->activateCompetition($competition);
        
        // Assert
        $this->assertFalse($result);
    }

    public function test_activate_competition_level_passed()
    {
        // Arrange
        $competition = $this->competition_partial;
        $competition->levels_number = 3;

            // Create a mock date that past
        $pastDate = now()->subDay(); 
            // Mock the start_date attribute to return a Carbon instance
        $competition->shouldReceive('getAttribute')
            ->with('start_date')
            ->andReturn($pastDate);

        $levelsCollection = Mockery::mock();
        $levelsCollection->shouldReceive('count')->andReturn(3);
        $competition->shouldReceive('getAttribute')->with('levels')->andReturn($levelsCollection);
        
        $usersCollection = Mockery::mock();
        $usersCollection->shouldReceive('count')->andReturn(3);
        $competition->shouldReceive('getAttribute')->with('users')->andReturn($usersCollection);
        
        $auditorsCollection = Mockery::mock();
        $auditorsCollection->shouldReceive('count')->andReturn(1);
        $competition->shouldReceive('getAttribute')->with('auditors')->andReturn($auditorsCollection);
        
        $competition->shouldReceive('isAllLevelAfterNow')->andReturn(false);
        $this->flasher
            ->shouldReceive('notify')
            ->with(__('messages.validation.not_allow.competition_activate_level_pass'), 'error')
            ->once();
        
        // Act
        $result = $this->competitionService->activateCompetition($competition);
        
        // Assert
        $this->assertFalse($result);
    }

    public function test_activate_competition_failure()
    {
        // Arrange
        $competition = $this->competition_partial;
        $competition->levels_number = 3;

            // Create a mock date that past
        $pastDate = now()->subDay(); 
            // Mock the start_date attribute to return a Carbon instance
        $competition->shouldReceive('getAttribute')
            ->with('start_date')
            ->andReturn($pastDate);

        $levelsCollection = Mockery::mock();
        $levelsCollection->shouldReceive('count')->andReturn(3);
        $competition->shouldReceive('getAttribute')->with('levels')->andReturn($levelsCollection);
        
        $usersCollection = Mockery::mock();
        $usersCollection->shouldReceive('count')->andReturn(3);
        $competition->shouldReceive('getAttribute')->with('users')->andReturn($usersCollection);
        
        $auditorsCollection = Mockery::mock();
        $auditorsCollection->shouldReceive('count')->andReturn(1);
        $competition->shouldReceive('getAttribute')->with('auditors')->andReturn($auditorsCollection);
        
        $competition->shouldReceive('isAllLevelAfterNow')->andReturn(true);
        
        $this->competitionRepository
            ->shouldReceive('activate')
            ->with($competition)
            ->once()
            ->andThrow(new Exception('Database error'));
        
        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) { return $callback(); });
        
        $this->flasher
            ->shouldReceive('notifyCrudResult')
            ->with(false, "activated")
            ->once();
        
        // Act
        $result = $this->competitionService->activateCompetition($competition);
        
        // Assert
        $this->assertFalse($result);
    }

    /** private methods */
    private function mockAdmin($role = 'owner',$hasRole = true): Admin | Mockery\MockInterface
    {
        $admin = Mockery::mock(Admin::class)->makePartial();
        $admin->id = 1;
        $admin->shouldReceive('hasRole')->with($role)->andReturn($hasRole);
        return $admin;
    }

} 