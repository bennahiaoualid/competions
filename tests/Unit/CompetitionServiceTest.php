<?php

namespace Tests\Unit;

use Bus;
use Mockery;
use Exception;
use Tests\TestCase;
use App\Models\Admin\Admin;
use App\Helpers\UserNotifyEmail;
use App\Contracts\FlasherInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use App\Models\Competition\Competition;
use App\Jobs\Competition\DeleteAuditorJob;
use App\Contracts\TransactionManagerInterface;
use App\Jobs\Competition\SafeDeleteAuditorJob;
use App\Services\Monitoring\JobTrackingService;
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
    /** @var JobTrackingService&\Mockery\MockInterface */
    protected $jobTrackingService;
    /** @var Competition|\Mockery\MockInterface */
    protected $competition;
    /** @var Admin|\Mockery\MockInterface */
    protected $admin;


    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mock repository & transaction manager & flasher
        $this->competitionRepository = Mockery::mock(CompetitionRepositoryInterface::class);
        $this->transactionManager = Mockery::mock(TransactionManagerInterface::class);
        $this->flasher = Mockery::mock(FlasherInterface::class);
        $this->jobTrackingService = Mockery::mock(JobTrackingService::class);
        
        $this->competitionService = new CompetitionService(
            $this->competitionRepository, 
            $this->transactionManager, 
            $this->flasher,
            $this->jobTrackingService
        );

        // Create a proper mock competition with id
        $this->competition = Mockery::mock(Competition::class);
        $this->competition->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(1);

        $this->admin = $this->mockAdmin('owner',true);
        
        // Mock Auth facade
        Auth::shouldReceive('id')->andReturn($this->admin->id);
        Auth::shouldReceive('user')->andReturn($this->admin);

        
        // Fake queues for job testing
        Queue::fake();
        Mockery::close();
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
    
        $this->competitionRepository
            ->shouldReceive('create')
            ->with($data)
            ->once()
            ->andReturn($this->competition);
        
        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) { return $callback(); });
    
        $this->flasher
            ->shouldReceive('crudSuccess')
            ->with("saved")
            ->once();
    
        // Act
        $result = $this->competitionService->createCompetition($data);
        
        // Assert
        $this->assertTrue($result);
        
        // Assert job was dispatched with correct parameters
        Queue::assertPushed(SyncCompetitionParticipants::class, function ($job) {
            return $job->competition->id === $this->competition->id && $job->isUpdate === false;
        });
    }

    public function test_create_competition_failure()
    {
        // Arrange
        $data = $this->createCompetitionData();

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
            ->shouldReceive('crudFailure')
            ->with("saved")
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
        $new_data = [
            'description' => 'new Description',
            'age_start' => 12, // This should trigger resync
            'age_end' => 15,
        ];

        $this->competition->shouldReceive('fill')->with($new_data)->andReturnSelf();

        $this->competitionRepository
            ->shouldReceive('update')
            ->with($this->competition, $new_data)
            ->once()
            ->andReturn([
                'competition' => $this->competition, 
                'resyncCompetitionParticipants' => true
            ]);
        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) { return $callback(); });     
        
        $this->flasher
            ->shouldReceive('crudSuccess')
            ->with("updated")
            ->once();

        // Act
        $result = $this->competitionService->updateCompetition($this->competition, $new_data);
        
        // Assert
        $this->assertTrue($result);
        Queue::assertPushed(SyncCompetitionParticipants::class, function ($job) {
            return $job->competition->id === $this->competition->id && $job->isUpdate === true;
        });
    }

    public function test_update_competition_success_without_resync()
    {
        // Arrange
        $new_data = [
            'description' => 'new Description',
            'age_start' => 12,
            'age_end' => 15,
        ];
        
        $this->competition->shouldReceive('fill')->with($new_data)->andReturnSelf();

        $this->competitionRepository
            ->shouldReceive('update')
            ->with($this->competition, $new_data)
            ->once()
            ->andReturn([
                'competition' => $this->competition, 
                'resyncCompetitionParticipants' => false
            ]);
        
        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) { return $callback(); });

        $this->flasher
            ->shouldReceive('crudSuccess')
            ->with("updated")
            ->once();

        // Mock UserNotifyEmail
        $userNotifyEmail = Mockery::mock('alias:' . UserNotifyEmail::class);
        $userNotifyEmail->shouldReceive('usersUpdateCompetition')
            ->with($this->competition)
            ->once();

        // Act
        $result = $this->competitionService->updateCompetition($this->competition, $new_data);
        
        // Assert
        $this->assertTrue($result);
        Queue::assertNothingPushed();
    }

    public function test_update_competition_failure()
    {
        // Arrange
        $new_data = [
            'description' => 'new Description',
        ];
        
        $this->competition->shouldReceive('fill')->with($new_data)->andReturnSelf();


        $this->competitionRepository
            ->shouldReceive('update')
            ->with($this->competition, $new_data)
            ->once()
            ->andThrow(new Exception('Database error'));

        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) { return $callback(); });

        $this->flasher
            ->shouldReceive('crudFailure')
            ->with("updated")
            ->once();

        // Act
        $result = $this->competitionService->updateCompetition($this->competition, $new_data);
        
        // Assert
        $this->assertFalse($result);
        Queue::assertNothingPushed();
    }

    public function test_delete_competition_success()
    {
        // Arrange
        
        $this->competition->shouldReceive('canEdit')->andReturn(true);
        
        // Mock Auth user with owner role
        $admin = $this->mockAdmin('owner',true);
        Auth::shouldReceive('user')->andReturn($admin);
        
        
        $this->competitionRepository
            ->shouldReceive('findById')
            ->with($this->competition->id)
            ->once()
            ->andReturn($this->competition);
            
        $this->competitionRepository
            ->shouldReceive('delete')
            ->with($this->competition)
            ->once();

        $this->flasher
            ->shouldReceive('crudSuccess')
            ->with("deleted")
            ->once();
        
        // Act
        $result = $this->competitionService->deleteCompetition($this->competition->id);
        
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
            ->shouldReceive('error')
            ->with(__('messages.validation.404.competition'))
            ->once();
        
        // Act
        $result = $this->competitionService->deleteCompetition($competitionId);
        
        // Assert
        $this->assertFalse($result);

    }

    public function test_delete_competition_unauthorized()
    {
        // Arrange

        // Mock Auth user without owner role
        $admin = $this->mockAdmin('owner',false);
        Auth::shouldReceive('user')->andReturn($admin);
        
        //  override canEdit method
        $this->competition->shouldReceive('canEdit')->andReturn(false);
        
        $this->competitionRepository
            ->shouldReceive('findById')
            ->with($this->competition->id)
            ->once()
            ->andReturn($this->competition);
        
        $this->flasher
            ->shouldReceive('error')
            ->with(__('messages.validation.not_allow.competition_delete'))
            ->once();

        // Act
        $result = $this->competitionService->deleteCompetition($this->competition->id);
        
        // Assert
        $this->assertFalse($result);
    }

    public function test_delete_competition_failure()
    {
        // Arrange
        $this->competition->shouldReceive('canEdit')->andReturn(true);
        
        // Mock Auth user with owner role
        $admin = $this->mockAdmin('owner',true);
        Auth::shouldReceive('user')->andReturn($admin);

        
        $this->competitionRepository
            ->shouldReceive('findById')
            ->with($this->competition->id)
            ->once()
            ->andReturn($this->competition);
            
        $this->competitionRepository
            ->shouldReceive('delete')
            ->with($this->competition)
            ->once()
            ->andThrow(new Exception('Database error'));

        $this->flasher
            ->shouldReceive('crudFailure')
            ->with("deleted")
            ->once();
        
        // Act
        $result = $this->competitionService->deleteCompetition($this->competition->id);
        
        // Assert
        $this->assertFalse($result);
    }

    public function test_add_competition_users_success()
    {
        // Arrange
        $user_ids = [1, 2, 3];
        
        $this->competitionRepository
            ->shouldReceive('addUsersToCompetition')
            ->with($this->competition, $user_ids)
            ->once();
        
        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) { return $callback(); });
        
        $this->flasher
            ->shouldReceive('crudSuccess')
            ->with("saved")
            ->once();
        
        // Act
        $result = $this->competitionService->addCompetitionUsers($this->competition, $user_ids);
        
        // Assert
        $this->assertTrue($result);
    }

    public function test_add_competition_users_failure()
    {
        // Arrange
        $user_ids = [1, 2, 3];
        
        $this->competitionRepository
            ->shouldReceive('addUsersToCompetition')
            ->with($this->competition, $user_ids)
            ->once()
            ->andThrow(new Exception('Database error'));
        
        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) { return $callback(); });
        
        $this->flasher
            ->shouldReceive('crudFailure')
            ->with("saved")
            ->once();
        
        // Act
        $result = $this->competitionService->addCompetitionUsers($this->competition, $user_ids);
        
        // Assert
        $this->assertFalse($result);
    }

    public function test_remove_competition_user_success()
    {
        // Arrange
        $this->competition->shouldReceive('canEdit')->andReturn(true);

        $user_id = 1;
            
        $this->competitionRepository
            ->shouldReceive('removeUserFromCompetition')
            ->with($this->competition, $user_id)
            ->once();
        
        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) { return $callback(); });
        
        $this->flasher
            ->shouldReceive('crudSuccess')
            ->with("deleted")
            ->once();
        
        // Act
        $result = $this->competitionService->removeCompetitionUser($this->competition, $user_id);
        
        // Assert
        $this->assertTrue($result);
    }

    public function test_remove_competition_user_failure()
    {
        // Arrange
        $this->competition->shouldReceive('canEdit')->andReturn(true);
        
        $user_id = 1;
            
        $this->competitionRepository
            ->shouldReceive('removeUserFromCompetition')
            ->with($this->competition, $user_id)
            ->once()
            ->andThrow(new Exception('Database error'));
        
        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) { return $callback(); });
        
        $this->flasher
            ->shouldReceive('crudFailure')
            ->with("deleted")
            ->once();
        
        // Act
        $result = $this->competitionService->removeCompetitionUser($this->competition, $user_id);
        
        // Assert
        $this->assertFalse($result);
    }

    public function test_add_competition_auditors_success()
    {
        // Arrange
        
        $this->competition->shouldReceive('canEdit')->andReturn(true);
        
        $auditor_ids = [1, 2, 3];
        
        $this->competitionRepository
            ->shouldReceive('addAuditorsToCompetition')
            ->with($this->competition, $auditor_ids)
            ->once();
        
        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) { return $callback(); });
        
        $this->flasher
            ->shouldReceive('crudSuccess')
            ->with("saved")
            ->once();
        
        // Mock UserNotifyEmail
        $userNotifyEmail = Mockery::mock('alias:' . UserNotifyEmail::class);
        $userNotifyEmail->shouldReceive('auditorNewCompetition')
            ->with($this->competition, $auditor_ids)
            ->once();
        
        // Act
        $result = $this->competitionService->addCompetitionAuditors($this->competition, $auditor_ids);
        
        // Assert
        $this->assertTrue($result);
    }

    public function test_add_competition_auditors_unauthorized()
    {
        // Arrange
        $this->competition->shouldReceive('canEdit')->andReturn(false);
        
        $auditor_ids = [1, 2, 3];
        
        $this->flasher
            ->shouldReceive('error')
            ->with(__('messages.validation.not_allow.competition_update'))
            ->once();
        
        // Act
        $result = $this->competitionService->addCompetitionAuditors($this->competition, $auditor_ids);
        
        // Assert
        $this->assertFalse($result);
    }

    public function test_add_competition_auditors_failure()
    {
        // Arrange
        $this->competition->shouldReceive('canEdit')->andReturn(true);
        
        $auditor_ids = [1, 2, 3];
        
        $this->competitionRepository
            ->shouldReceive('addAuditorsToCompetition')
            ->with($this->competition, $auditor_ids)
            ->once()
            ->andThrow(new Exception('Database error'));
        
        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function ($callback) { return $callback(); });
        
        $this->flasher
            ->shouldReceive('crudFailure')
            ->with("saved")
            ->once();
        
        // Act
        $result = $this->competitionService->addCompetitionAuditors($this->competition, $auditor_ids);
        
        // Assert
        $this->assertFalse($result);
    }

    public function test_remove_competition_auditor_success()
    {
        // Arrange
        Bus::fake();
    
        $this->competition->shouldReceive('canEdit')->andReturn(true);
        // Mock auditors collection with count = 2
        $auditorsCollection = Mockery::mock();
        $auditorsCollection->shouldReceive('count')->andReturn(2);
        $this->competition->shouldReceive('getAttribute')->with('auditors')->andReturn($auditorsCollection);
        
        $auditor_id = $this->admin->id;
        
        $this->competitionRepository
        ->shouldReceive('getAdmin')
        ->once()
        ->with($auditor_id)
        ->andReturn($this->admin);

        $this->jobTrackingService
            ->shouldReceive('dispatchWithTracking')
            ->once()
            ->andReturn('tracking-id');
        
        $this->flasher
            ->shouldReceive('info')
            ->with(__('messages.validation.info.deleted'))
            ->once();
        
        // Act
        $result = $this->competitionService->removeCompetitionAuditor($this->competition, $auditor_id);
        
        // Assert
        $this->assertTrue($result);
    }

    public function test_remove_competition_auditor_unauthorized()
    {
        Bus::fake();
        // Arrange
        $this->competition->shouldReceive('canEdit')->andReturn(false);
        
        $auditor_id = 1;
            
        $this->flasher
            ->shouldReceive('error')
            ->with(__('messages.validation.not_allow.competition_update'))
            ->once();
        
        // Act
        $result = $this->competitionService->removeCompetitionAuditor($this->competition, $auditor_id);
        
        // Assert
        $this->assertFalse($result);
    }

    public function test_remove_competition_auditor_last_auditor()
    {
        // Arrange
        Bus::fake();
        $this->competition->shouldReceive('canEdit')->andReturn(true);
        // Mock auditors collection with count = 1
        $auditorsCollection = Mockery::mock();
        $auditorsCollection->shouldReceive('count')->andReturn(1);
        $this->competition->shouldReceive('getAttribute')->with('auditors')->andReturn($auditorsCollection);
        
        $auditor_id = 1;
            
        $this->flasher
            ->shouldReceive('error')
            ->with(__('messages.validation.not_allow.remove_auditor_only_one'))
            ->once();
        
        // Act
        $result = $this->competitionService->removeCompetitionAuditor($this->competition, $auditor_id);
        
        // Assert
        $this->assertFalse($result);
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

        $competition = $this->competition;
        
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
            ->shouldReceive('crudSuccess')
            ->with("activated")
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
        
        $competition = $this->competition;

        // Mock the start_date attribute to return a Carbon instance
        $competition->shouldReceive('getAttribute')
            ->with('start_date')
            ->andReturn($futureDate);

        
        $this->flasher
            ->shouldReceive('error')
            ->with(__('messages.validation.not_allow.competition_activate_early'))
            ->once();
        
        // Act
        $result = $this->competitionService->activateCompetition($competition);
        
        // Assert
        $this->assertFalse($result);
    }

    public function test_activate_competition_levels_mismatch()
    {

        // Arrange
        $competition = $this->competition;
        $competition->shouldReceive('getAttribute')->with('levels_number')->andReturn(3);

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
            ->shouldReceive('error')
            ->with(__('messages.validation.not_allow.competition_activate_match_levels'))
            ->once();
        
        // Act
        $result = $this->competitionService->activateCompetition($competition);
        
        // Assert
        $this->assertFalse($result);
    }

    public function test_activate_competition_insufficient_competitors()
    {
        // Arrange
        $competition = $this->competition;
        $competition->shouldReceive('getAttribute')->with('levels_number')->andReturn(3);

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
            ->shouldReceive('error')
            ->with(__('messages.validation.not_allow.competition_activate_less_competitors'))
            ->once();
        
        // Act
        $result = $this->competitionService->activateCompetition($competition);
        
        // Assert
        $this->assertFalse($result);
    }

    public function test_activate_competition_no_auditors()
    {
        // Arrange
        $competition = $this->competition;
        $competition->shouldReceive('getAttribute')->with('levels_number')->andReturn(3);

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
            ->shouldReceive('error')
            ->with(__('messages.validation.not_allow.competition_activate_less_auditor'))
            ->once();
        
        // Act
        $result = $this->competitionService->activateCompetition($competition);
        
        // Assert
        $this->assertFalse($result);
    }

    public function test_activate_competition_level_passed()
    {
        // Arrange
        $competition = $this->competition;
        $competition->shouldReceive('getAttribute')->with('levels_number')->andReturn(3);

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
            ->shouldReceive('error')
            ->with(__('messages.validation.not_allow.competition_activate_level_pass'))
            ->once();
        
        // Act
        $result = $this->competitionService->activateCompetition($competition);
        
        // Assert
        $this->assertFalse($result);
    }

    public function test_activate_competition_failure()
    {
        // Arrange
        $competition = $this->competition;
        $competition->shouldReceive('getAttribute')->with('levels_number')->andReturn(3);

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
            ->shouldReceive('crudFailure')
            ->with("activated")
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
        $admin->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $admin->shouldReceive('getAttribute')->with('name')->andReturn('admin');
        $admin->shouldReceive('getAttribute')->with('email')->andReturn('admin@admin.com');
        
        $admin->shouldReceive('hasRole')->with($role)->andReturn($hasRole);
        return $admin;
    }
} 