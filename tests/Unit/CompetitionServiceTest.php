<?php

namespace Tests\Unit;

use Mockery;
use Exception;
use Tests\TestCase;
use App\Models\User;
use App\Models\Admin\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Helpers\AuditorSaveDelete;
use App\Models\Competition\Competition;
use Illuminate\Support\Facades\Session;
use App\Services\Competition\CompetitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Jobs\Competetion\SyncCompetitionParticipants;
use App\Interface\Competition\CompetitionRepositoryInterface;
use App\Http\Helpers\UserNotifyEmail;

class CompetitionServiceTest extends TestCase
{
    use RefreshDatabase;
    /** @var CompetitionService&\Mockery\MockInterface */
    protected $competitionService;
    /** @var CompetitionRepositoryInterface&\Mockery\MockInterface */
    protected $competitionRepository;
    /** @var Admin */
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mock repository
        $this->competitionRepository = Mockery::mock(CompetitionRepositoryInterface::class);
        
        $this->competitionService = new CompetitionService($this->competitionRepository);
        // Create test admin
        $this->admin = Admin::factory()->create();
        
        // Mock Auth facade
        Auth::shouldReceive('id')->andReturn($this->admin->id);
        
        // Mock database transactions globally for all tests
        /*DB::shouldReceive('beginTransaction')->byDefault();
        DB::shouldReceive('commit')->byDefault();
        DB::shouldReceive('rollback')->byDefault();*/
        
        // Fake queues for job testing
        \Illuminate\Support\Facades\Queue::fake();
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
        $competition = Competition::factory()->make($data); // Use make() instead of create()
    
        // Set up specific expectations for this test
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        DB::shouldReceive('rollback')->never();
    
        $this->competitionRepository
            ->shouldReceive('create')
            ->with($data)
            ->once()
            ->andReturn($competition);
    
        // Act
        $result = $this->competitionService->createCompetition($data);
        
        // Assert
        $this->assertTrue($result);
        $this->assertTrue(Session::has('messages'));
        
        // Assert job was dispatched with correct parameters
        \Illuminate\Support\Facades\Queue::assertPushed(SyncCompetitionParticipants::class, function ($job) use ($competition) {
            return $job->competition->id === $competition->id && $job->isUpdate === false;
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

        // Act
        $result = $this->competitionService->createCompetition($data);
        
        // Assert
        $this->assertFalse($result);
        $this->assertTrue(Session::has('messages'));
    }

    public function test_create_competition_with_missing_required_fields()
    {
        // Arrange
        $data = $this->createCompetitionData();
        unset($data['title']); // Remove required field

        // Act
        $result = $this->competitionService->createCompetition($data);
        
        // Assert
        $this->assertFalse($result);
        $this->assertTrue(Session::has('messages'));
    }

    public function test_create_competition_with_invalid_date()
    {
        // Arrange
        $data = $this->createCompetitionData([
            'start_date' => 'invalid-date'
        ]);

        // Act
        $result = $this->competitionService->createCompetition($data);
        
        // Assert
        $this->assertFalse($result);
        $this->assertTrue(Session::has('messages'));
    }

    public function test_create_competition_with_invalid_age_range()
    {
        // Arrange
        $data = $this->createCompetitionData([
            'age_start' => 20,
            'age_end' => 10 // age_end should be greater than age_start
        ]);

        // Act
        $result = $this->competitionService->createCompetition($data);
        
        // Assert
        $this->assertFalse($result);
        $this->assertTrue(Session::has('messages'));
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
        $competition = Competition::factory()->create($data); 
    
        // Set up specific expectations for this test
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        DB::shouldReceive('rollback')->never();
    
        $this->competitionRepository
            ->shouldReceive('update')
            ->with($competition, $new_data)
            ->once()
            ->andReturn([
                'competition' => $competition->fill($new_data), 
                'resyncCompetitionParticipants' => true
            ]);
    
        // Act
        $result = $this->competitionService->updateCompetition($competition, $new_data);
        
        // Assert
        $this->assertTrue($result);
        $this->assertTrue(Session::has('messages'));
        $this->assertStringContainsString(
            trans('messages.validation.success.updated'),
            Session::get('messages')[0]['message']);
    }

    public function test_update_competition_success_without_resync()
    {
        // Arrange
        $data = $this->createCompetitionData();
        $new_data = [
            'description' => 'new Description',
            'title' => 'Updated Title',
        ];
        $competition = Competition::factory()->create($data); 
    
        // Set up specific expectations for this test
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        DB::shouldReceive('rollback')->never();
    
        $this->competitionRepository
            ->shouldReceive('update')
            ->with($competition, $new_data)
            ->once()
            ->andReturn([
                'competition' => $competition->fill($new_data), 
                'resyncCompetitionParticipants' => false
            ]);
    
        // Act
        $result = $this->competitionService->updateCompetition($competition, $new_data);
        
        // Assert
        $this->assertTrue($result);
        $this->assertTrue(Session::has('messages'));
        $this->assertStringContainsString(
            trans('messages.validation.success.updated'),
            Session::get('messages')[0]['message']);
        
        // Assert no job was dispatched
        \Illuminate\Support\Facades\Queue::assertNothingPushed();
    }

    public function test_update_competition_failure()
    {
        // Arrange
        $data = $this->createCompetitionData();
        $new_data = [
            'description' => 'new Description',
        ];
        $competition = Competition::factory()->create($data); 
    
        // Set up specific expectations for this test
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollback')->once();
        DB::shouldReceive('commit')->never();
    
        $this->competitionRepository
            ->shouldReceive('update')
            ->with($competition, $new_data)
            ->once()
            ->andThrow(new Exception('Database error'));
    
        // Act
        $result = $this->competitionService->updateCompetition($competition, $new_data);
        
        // Assert
        $this->assertFalse($result);
        $this->assertTrue(Session::has('messages'));
        $this->assertStringContainsString(
            trans('messages.validation.fail.updated'),
            Session::get('messages')[0]['message']);
    }

    public function test_delete_competition_success()
    {
        // Arrange
        $data = $this->createCompetitionData();
        $competition = Competition::factory()->create($data);
        
        // Mock Auth user with owner role
        $user = Mockery::mock(Admin::class);
        $user->shouldReceive('hasRole')->with('owner')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($user);
        
        // Set up specific expectations for this test
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        DB::shouldReceive('rollback')->never();
        
        $this->competitionRepository
            ->shouldReceive('findById')
            ->with($competition->id)
            ->once()
            ->andReturn($competition);
            
        $this->competitionRepository
            ->shouldReceive('delete')
            ->with($competition)
            ->once();
        
        // Act
        $result = $this->competitionService->deleteCompetition($competition->id);
        
        // Assert
        $this->assertTrue($result);
        $this->assertTrue(Session::has('messages'));
        $this->assertStringContainsString(
            trans('messages.validation.success.deleted'),
            Session::get('messages')[0]['message']
        );
    }

    public function test_delete_competition_not_found()
    {
        // Arrange
        $competitionId = 'non-existent-id';
        
        $this->competitionRepository
            ->shouldReceive('findById')
            ->with($competitionId)
            ->once()
            ->andReturn(null);
        
        // Act
        $result = $this->competitionService->deleteCompetition($competitionId);
        
        // Assert
        $this->assertFalse($result);
        $this->assertTrue(Session::has('messages'));
        $this->assertStringContainsString(
            'Competition not found',
            Session::get('messages')[0]['message']
        );
    }

    public function test_delete_competition_unauthorized()
    {
        // Arrange
        $data = $this->createCompetitionData();
        $competition = Competition::factory()->create($data);
        
        // Mock Auth user without owner role
        $user = Mockery::mock(Admin::class);
        $user->shouldReceive('hasRole')->with('owner')->andReturn(false);
        Auth::shouldReceive('user')->andReturn($user);
        
        // Create a partial mock of the competition to override canEdit method
        $competitionMock = Mockery::mock($competition)->makePartial();
        $competitionMock->shouldReceive('canEdit')->andReturn(false);
        
        $this->competitionRepository
            ->shouldReceive('findById')
            ->with($competition->id)
            ->once()
            ->andReturn($competitionMock);
        
        // Act
        $result = $this->competitionService->deleteCompetition($competition->id);
        
        // Assert
        $this->assertFalse($result);
        $this->assertTrue(Session::has('messages'));
        $this->assertStringContainsString(
            trans('messages.validation.not_allow.competition_delete'),
            Session::get('messages')[0]['message']
        );
    }

    public function test_delete_competition_failure()
    {
        // Arrange
        $data = $this->createCompetitionData();
        $competition = Competition::factory()->create($data);
        
        // Mock Auth user with owner role
        $user = Mockery::mock(User::class);
        $user->shouldReceive('hasRole')->with('owner')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($user);
        
        // Set up specific expectations for this test
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollback')->once();
        DB::shouldReceive('commit')->never();
        
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
        
        // Act
        $result = $this->competitionService->deleteCompetition($competition->id);
        
        // Assert
        $this->assertFalse($result);
        $this->assertTrue(Session::has('messages'));
        $this->assertStringContainsString(
            trans('messages.validation.fail.deleted'),
            Session::get('messages')[0]['message']
        );
    }

    public function test_add_competition_users_success()
    {
        // Arrange
        $data = $this->createCompetitionData();
        $competition = Competition::factory()->create($data); 
        $user_ids = [1, 2, 3];
        
        // Set up specific expectations for this test
        $this->competitionRepository
            ->shouldReceive('addUsersToCompetition')
            ->with($competition, $user_ids)
            ->once()
            ->andReturn(true);  
    
        // Act
        $result = $this->competitionService->addCompetitionUsers($competition, $user_ids);
        
        // Assert
        $this->assertTrue($result);
        $this->assertTrue(Session::has('messages'));
        $this->assertStringContainsString(
            trans('messages.validation.success.saved'),
            Session::get('messages')[0]['message']
        );
    }

    public function test_add_competition_users_failure()
    {
        // Arrange
        $data = $this->createCompetitionData();
        $competition = Competition::factory()->create($data); 
        $user_ids = [1, 2, 3];  
        
        // Set up specific expectations for this test
        $this->competitionRepository
            ->shouldReceive('addUsersToCompetition')
            ->with($competition, $user_ids)
            ->once()
            ->andThrow(new Exception('Database error')); 
    
        // Act
        $result = $this->competitionService->addCompetitionUsers($competition, $user_ids);
        
        // Assert
        $this->assertFalse($result);
        $this->assertTrue(Session::has('messages'));    
        $this->assertStringContainsString(
            trans('messages.validation.fail.saved'),
            Session::get('messages')[0]['message']
        );
    }

    public function test_remove_competition_user_success()
    {
        // Arrange
        $competition = Competition::factory()->make(['id' => 1]);
        $user_id = 1;
        
        // Mock repository behavior
        $this->competitionRepository
            ->shouldReceive('findById')
            ->with($competition->id)
            ->once()
            ->andReturn($competition);
            
        $this->competitionRepository
            ->shouldReceive('removeUserFromCompetition')
            ->with($competition, $user_id)
            ->once()
            ->andReturn(true);

        // Act
        $result = $this->competitionService->removeCompetitionUser($competition->id, $user_id);
        
        // Assert - Test only what the SERVICE does
        $this->assertTrue($result);
        $this->assertStringContainsString(
            trans('messages.validation.success.deleted'),
            Session::get('messages')[0]['message']
        );
        
        // DON'T test database state in unit tests:
        // $this->assertEquals(0, $competition->users()->count()); // ❌ This is integration testing
    }

    public function test_remove_competition_user_when_competition_not_found()
    {
        // Arrange
        $competition_id = 999;
        $user_id = 1;
        
        $this->competitionRepository
            ->shouldReceive('findById')
            ->with($competition_id)
            ->once()
            ->andReturn(null);

        // Act
        $result = $this->competitionService->removeCompetitionUser($competition_id, $user_id);
        
        // Assert
        $this->assertFalse($result);
    }

    public function test_remove_competition_user_when_repository_throws_exception()
    {
        // Arrange
        $competition = Competition::factory()->make(['id' => 1]);
        $user_id = 1;
        
        $this->competitionRepository
            ->shouldReceive('findById')
            ->with($competition->id)
            ->once()
            ->andReturn($competition);
            
        $this->competitionRepository
            ->shouldReceive('removeUserFromCompetition')
            ->with($competition, $user_id)
            ->once()
            ->andThrow(new Exception('Database error'));

        // Act
        $result = $this->competitionService->removeCompetitionUser($competition->id, $user_id);
        
        // Assert
        $this->assertFalse($result);
        $this->assertStringContainsString(
            trans('messages.validation.fail.deleted'),
            Session::get('messages')[0]['message']
        );
    }
    
    public function test_add_competition_auditors_success()
    {
        // Arrange
        $data = $this->createCompetitionData();
        $competition = Competition::factory()->create($data); 
        $auditor_ids = [1, 2, 3];
        
        // Set up specific expectations for this test
        $this->competitionRepository
            ->shouldReceive('addAuditorsToCompetition')
            ->with($competition, $auditor_ids)
            ->once()
            ->andReturn(true);  
    
        // Act
        $result = $this->competitionService->addCompetitionAuditors($competition, $auditor_ids);
        
        // Assert
        $this->assertTrue($result);
        $this->assertTrue(Session::has('messages'));
        $this->assertStringContainsString(
            trans('messages.validation.success.saved'),
            Session::get('messages')[0]['message']
        );
    }

    public function test_add_competition_auditors_failure()
    {
        // Arrange
        $data = $this->createCompetitionData();
        $competition = Competition::factory()->create($data); 
        $auditor_ids = [1, 2, 3];  
        
        // Set up specific expectations for this test
        $this->competitionRepository
            ->shouldReceive('addAuditorsToCompetition')
            ->with($competition, $auditor_ids)
            ->once()
            ->andThrow(new Exception('Database error')); 
    
        // Act
            $result = $this->competitionService->addCompetitionAuditors($competition, $auditor_ids);
        
        // Assert
        $this->assertFalse($result);
        $this->assertTrue(Session::has('messages'));    
        $this->assertStringContainsString(
            trans('messages.validation.fail.saved'),
            Session::get('messages')[0]['message']
        );
    }

    public function test_add_competition_auditor_unauthorized()
    {
        // Arrange
        $auditor_ids = [1, 2, 3];

        // Mock Auth user without owner role
        $user = Mockery::mock(Admin::class);
        $user->shouldReceive('hasRole')->with('owner')->andReturn(false);
        Auth::shouldReceive('user')->andReturn($user);

        // Create a mock competition that returns false for canEdit
        /** @var Competition|\Mockery\MockInterface $mock_competition */
        $mock_competition = Mockery::mock(Competition::class)->shouldIgnoreMissing();
        $mock_competition->shouldReceive('canEdit')->andReturn(false);
        $mock_competition->id = 1; // Set an ID if needed
        
        // Act
        $result = $this->competitionService->addCompetitionAuditors($mock_competition, $auditor_ids);
        
        // Assert
        $this->assertFalse($result);
        $this->assertTrue(Session::has('messages'));
        $this->assertStringContainsString(
            trans('messages.validation.not_allow.competition_update'),
            Session::get('messages')[0]['message']
        );
        
    }
    

    
    public function test_remove_auditor_competition_not_found()
    {
        // Mock: findById returns null
        $this->competitionRepository
            ->shouldReceive('findById')
            ->with(999)
            ->once()
            ->andReturn(null);

        $result = $this->competitionService->removeCompetitionAuditor(999, 1);
        
        $this->assertFalse($result);
    }

    
    public function test_remove_auditor_unauthorized()
    {
        /** @var Competition|\Mockery\MockInterface $mock_competition */
        $mock_competition = Mockery::mock(Competition::class)->shouldIgnoreMissing();
        $mock_competition->shouldReceive('canEdit')->andReturn(false);

        $this->competitionRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($mock_competition);

        $result = $this->competitionService->removeCompetitionAuditor(1, 1);
        
        $this->assertFalse($result);
        $this->assertStringContainsString(
            trans('messages.validation.not_allow.competition_update'),
            Session::get('messages')[0]['message']
        );
    }

    
    public function test_remove_auditor_only_one_auditor_left()
    {
        // Mock auditors collection with count = 1
        $auditorsCollection = Mockery::mock();
        $auditorsCollection->shouldReceive('count')->andReturn(1);

        /** @var Competition|\Mockery\MockInterface $mock_competition */
        $mock_competition = Mockery::mock(Competition::class)->shouldIgnoreMissing();
        $mock_competition->shouldReceive('canEdit')->andReturn(true);
        $mock_competition->shouldReceive('getAttribute')->with('auditors')->andReturn($auditorsCollection);

        $this->competitionRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($mock_competition);

        $result = $this->competitionService->removeCompetitionAuditor(1, 1);
        
        $this->assertFalse($result);
        $this->assertStringContainsString(
            trans('messages.validation.not_allow.remove_auditor_only_one'),
            Session::get('messages')[0]['message']
        );
    }

    
    public function test_remove_auditor_save_delete_fails()
    {
        // Mock auditors collection with count > 1
        $auditorsCollection = Mockery::mock();
        $auditorsCollection->shouldReceive('count')->andReturn(2);

        /** @var Competition|\Mockery\MockInterface $mock_competition */
        $mock_competition = Mockery::mock(Competition::class)->shouldIgnoreMissing();
        $mock_competition->shouldReceive('canEdit')->andReturn(true);
        $mock_competition->shouldReceive('getAttribute')->with('auditors')->andReturn($auditorsCollection);

        $this->competitionRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($mock_competition);

        // Mock the static method using Mockery's static mock
        $auditorSaveDelete = Mockery::mock('alias:' . AuditorSaveDelete::class);
        $auditorSaveDelete->shouldReceive('deleteAuditor')
            ->with(1, $mock_competition)
            ->once()
            ->andThrow(new Exception("Error Processing Request"));

        $result = $this->competitionService->removeCompetitionAuditor(1, 1);
        
        $this->assertFalse($result);
        $this->assertStringContainsString(
            trans('messages.validation.fail.deleted'),
            Session::get('messages')[0]['message']
        );
    }

    
    public function test_remove_auditor_success()
    {
        // Mock auditors collection with count > 1
        $auditorsCollection = Mockery::mock();
        $auditorsCollection->shouldReceive('count')->andReturn(2);

        /** @var Competition|\Mockery\MockInterface $mock_competition */
        $mock_competition = Mockery::mock(Competition::class)->shouldIgnoreMissing();
        $mock_competition->shouldReceive('canEdit')->andReturn(true);
        $mock_competition->shouldReceive('getAttribute')->with('auditors')->andReturn($auditorsCollection);

        $this->competitionRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($mock_competition);

        // Mock static method to return true
        $auditorSaveDelete = Mockery::mock('alias:' . AuditorSaveDelete::class);
        $auditorSaveDelete->shouldReceive('deleteAuditor')
            ->with(1, $mock_competition)
            ->once()
            ->andReturn(true);

        // Mock repository removal
        $this->competitionRepository
            ->shouldReceive('removeAuditorFromCompetition')
            ->with($mock_competition, 1)
            ->once()
            ->andReturn(true);

        $result = $this->competitionService->removeCompetitionAuditor(1, 1);
        
        $this->assertTrue($result);
        $this->assertStringContainsString(
            trans('messages.validation.success.deleted'),
            Session::get('messages')[0]['message']
        );
    }

    
    public function test_remove_auditor_repository_exception()
    {
        // Mock auditors collection with count > 1
        $auditorsCollection = Mockery::mock();
        $auditorsCollection->shouldReceive('count')->andReturn(2);

        /** @var Competition|\Mockery\MockInterface $mock_competition */
        $mock_competition = Mockery::mock(Competition::class)->shouldIgnoreMissing();
        $mock_competition->shouldReceive('canEdit')->andReturn(true);
        $mock_competition->shouldReceive('getAttribute')->with('auditors')->andReturn($auditorsCollection);

        $this->competitionRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($mock_competition);

        $auditorSaveDelete = Mockery::mock('alias:' . AuditorSaveDelete::class);
        $auditorSaveDelete->shouldReceive('deleteAuditor')
            ->with(1, $mock_competition)
            ->once()
            ->andReturn(true);

        // Mock repository to throw exception
        $this->competitionRepository
            ->shouldReceive('removeAuditorFromCompetition')
            ->with($mock_competition, 1)
            ->once()
            ->andThrow(new Exception('Database error'));

        $result = $this->competitionService->removeCompetitionAuditor(1, 1);
        
        $this->assertFalse($result);
        $this->assertStringContainsString(
            trans('messages.validation.fail.deleted'),
            Session::get('messages')[0]['message']
        );
    }  

    public function test_activate_competition_fails_if_start_date_is_future_or_now()
    {
        // Create a mock date that's in the future (should fail)
        $futureDate = now()->addDay();
        
        /** @var Competition|\Mockery\MockInterface $mock_competition */
        $mock_competition = Mockery::mock(Competition::class)->shouldIgnoreMissing();
        
        // Mock the start_date attribute to return a Carbon instance
        $mock_competition->shouldReceive('getAttribute')
            ->with('start_date')
            ->andReturn($futureDate);
        
        // Act
        $result = $this->competitionService->activateCompetition($mock_competition);
        
        // Assert
        $this->assertFalse($result);
        $this->assertTrue(Session::has('messages'));
        $this->assertStringContainsString(
            trans('messages.validation.not_allow.competition_activate_early'),
            Session::get('messages')[0]['message']
        );
    }

    public function test_activate_competition_fails_if_levels_count_mismatch()
    {
        // Start date is in the past (should pass first check)
        $pastDate = now()->subDay();
        
        // Levels collection with wrong count
        $levelsCollection = Mockery::mock();
        $levelsCollection->shouldReceive('count')->andReturn(2); // Different from levels_number
        
        /** @var Competition|\Mockery\MockInterface $mock_competition */
        $mock_competition = Mockery::mock(Competition::class)->shouldIgnoreMissing();
        
        // Mock attributes
        $mock_competition->shouldReceive('getAttribute')->with('start_date')->andReturn($pastDate);
        $mock_competition->shouldReceive('getAttribute')->with('levels')->andReturn($levelsCollection);
        $mock_competition->shouldReceive('getAttribute')->with('levels_number')->andReturn(3); // Mismatch!
        
        // Act
        $result = $this->competitionService->activateCompetition($mock_competition);
        
        // Assert
        $this->assertFalse($result);
        $this->assertTrue(Session::has('messages'));
        $this->assertStringContainsString(
            trans('messages.validation.not_allow.competition_activate_match_levels'),
            Session::get('messages')[0]['message']
        );
    }

    public function test_activate_competition_fails_if_insufficient_users()
    {
        // Mock all previous validations to pass
        $pastDate = now()->subDay();
        
        $levelsCollection = Mockery::mock();
        $levelsCollection->shouldReceive('count')->andReturn(3);
        
        $usersCollection = Mockery::mock();
        $usersCollection->shouldReceive('count')->andReturn(2); // <= 2 should fail
        
        /** @var Competition|\Mockery\MockInterface $mock_competition */
        $mock_competition = Mockery::mock(Competition::class)->shouldIgnoreMissing();
        
        $mock_competition->shouldReceive('getAttribute')->with('start_date')->andReturn($pastDate);
        $mock_competition->shouldReceive('getAttribute')->with('levels')->andReturn($levelsCollection);
        $mock_competition->shouldReceive('getAttribute')->with('levels_number')->andReturn(3);
        $mock_competition->shouldReceive('getAttribute')->with('users')->andReturn($usersCollection);
        
        // Act
        $result = $this->competitionService->activateCompetition($mock_competition);
        
        // Assert
        $this->assertFalse($result);
        $this->assertTrue(Session::has('messages'));
        $this->assertStringContainsString(
            trans('messages.validation.not_allow.competition_activate_less_competitors'),
            Session::get('messages')[0]['message']
        );
    }

    public function test_activate_competition_fails_if_no_auditors()
    {
        // Mock all previous validations to pass
        $pastDate = now()->subDay();
        
        $levelsCollection = Mockery::mock();
        $levelsCollection->shouldReceive('count')->andReturn(3);
        
        $usersCollection = Mockery::mock();
        $usersCollection->shouldReceive('count')->andReturn(5); // > 2, should pass
        
        $auditorsCollection = Mockery::mock();
        $auditorsCollection->shouldReceive('count')->andReturn(0); // Should fail
        
        /** @var Competition|\Mockery\MockInterface $mock_competition */
        $mock_competition = Mockery::mock(Competition::class)->shouldIgnoreMissing();
        
        $mock_competition->shouldReceive('getAttribute')->with('start_date')->andReturn($pastDate);
        $mock_competition->shouldReceive('getAttribute')->with('levels')->andReturn($levelsCollection);
        $mock_competition->shouldReceive('getAttribute')->with('levels_number')->andReturn(3);
        $mock_competition->shouldReceive('getAttribute')->with('users')->andReturn($usersCollection);
        $mock_competition->shouldReceive('getAttribute')->with('auditors')->andReturn($auditorsCollection);
        
        // Act
        $result = $this->competitionService->activateCompetition($mock_competition);
        
        // Assert
        $this->assertFalse($result);
        $this->assertTrue(Session::has('messages'));
        $this->assertStringContainsString(
            trans('messages.validation.not_allow.competition_activate_less_auditor'),
            Session::get('messages')[0]['message']
        );
    }

    public function test_activate_competition_fails_if_levels_not_all_after_now()
    {
        // Mock all previous validations to pass
        $pastDate = now()->subDay();
        
        $levelsCollection = Mockery::mock();
        $levelsCollection->shouldReceive('count')->andReturn(3);
        
        $usersCollection = Mockery::mock();
        $usersCollection->shouldReceive('count')->andReturn(5);
        
        $auditorsCollection = Mockery::mock();
        $auditorsCollection->shouldReceive('count')->andReturn(2);
        
        /** @var Competition|\Mockery\MockInterface $mock_competition */
        $mock_competition = Mockery::mock(Competition::class)->shouldIgnoreMissing();
        
        $mock_competition->shouldReceive('getAttribute')->with('start_date')->andReturn($pastDate);
        $mock_competition->shouldReceive('getAttribute')->with('levels')->andReturn($levelsCollection);
        $mock_competition->shouldReceive('getAttribute')->with('levels_number')->andReturn(3);
        $mock_competition->shouldReceive('getAttribute')->with('users')->andReturn($usersCollection);
        $mock_competition->shouldReceive('getAttribute')->with('auditors')->andReturn($auditorsCollection);
        
        // Mock the model method
        $mock_competition->shouldReceive('isAllLevelAfterNow')->andReturn(false); // Should fail
        
        // Act
        $result = $this->competitionService->activateCompetition($mock_competition);
        
        // Assert
        $this->assertFalse($result);
        $this->assertTrue(Session::has('messages'));
        $this->assertStringContainsString(
            trans('messages.validation.not_allow.competition_activate_level_pass'),
            Session::get('messages')[0]['message']
        );
    }

    public function test_activate_competition_success()
    {
        // Mock all validations to pass
        $pastDate = now()->subDay();
        
        $levelsCollection = Mockery::mock();
        $levelsCollection->shouldReceive('count')->andReturn(3);
        
        $usersCollection = Mockery::mock();
        $usersCollection->shouldReceive('count')->andReturn(5);
        
        $auditorsCollection = Mockery::mock();
        $auditorsCollection->shouldReceive('count')->andReturn(2);
        
        /** @var Competition|\Mockery\MockInterface $mock_competition */
        $mock_competition = Mockery::mock(Competition::class)->shouldIgnoreMissing();
        
        $mock_competition->shouldReceive('getAttribute')->with('start_date')->andReturn($pastDate);
        $mock_competition->shouldReceive('getAttribute')->with('levels')->andReturn($levelsCollection);
        $mock_competition->shouldReceive('getAttribute')->with('levels_number')->andReturn(3);
        $mock_competition->shouldReceive('getAttribute')->with('users')->andReturn($usersCollection);
        $mock_competition->shouldReceive('getAttribute')->with('auditors')->andReturn($auditorsCollection);
        $mock_competition->shouldReceive('isAllLevelAfterNow')->andReturn(true);
        
        // Mock setAttribute for start_date assignment
        $mock_competition->shouldReceive('setAttribute')->with('start_date', Mockery::any())->andReturnSelf();
        
        // Mock database operations
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        
        // Mock repository
        $this->competitionRepository->shouldReceive('activate')
            ->with($mock_competition)
            ->once()
            ->andReturn(true);
        
        // Mock notification
        $userNotifyEmailMock = Mockery::mock('alias:' . UserNotifyEmail::class);

        $userNotifyEmailMock->shouldReceive('usersActivateCompetition')
            ->with($mock_competition)
            ->once();
        
        // Act
        $result = $this->competitionService->activateCompetition($mock_competition);
        
        // Assert
        $this->assertTrue($result);
        $this->assertStringContainsString(
            trans('messages.validation.success.activated'),
            Session::get('messages')[0]['message']
        );
    }

    public function test_activate_competition_database_exception()
    {
        // Mock all validations to pass
        $pastDate = now()->subDay();
        
        $levelsCollection = Mockery::mock();
        $levelsCollection->shouldReceive('count')->andReturn(3);
        
        $usersCollection = Mockery::mock();
        $usersCollection->shouldReceive('count')->andReturn(5);
        
        $auditorsCollection = Mockery::mock();
        $auditorsCollection->shouldReceive('count')->andReturn(2);
        
        /** @var Competition|\Mockery\MockInterface $mock_competition */
        $mock_competition = Mockery::mock(Competition::class)->shouldIgnoreMissing();
        
        $mock_competition->shouldReceive('getAttribute')->with('start_date')->andReturn($pastDate);
        $mock_competition->shouldReceive('getAttribute')->with('levels')->andReturn($levelsCollection);
        $mock_competition->shouldReceive('getAttribute')->with('levels_number')->andReturn(3);
        $mock_competition->shouldReceive('getAttribute')->with('users')->andReturn($usersCollection);
        $mock_competition->shouldReceive('getAttribute')->with('auditors')->andReturn($auditorsCollection);
        $mock_competition->shouldReceive('isAllLevelAfterNow')->andReturn(true);
        $mock_competition->shouldReceive('setAttribute')->with('start_date', Mockery::any())->andReturnSelf();
        
        // Mock database operations with exception
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollback')->once();
        
        // Mock repository to throw exception
        $this->competitionRepository->shouldReceive('activate')
            ->with($mock_competition)
            ->once()
            ->andThrow(new Exception('Database error'));
        
        // Act
        $result = $this->competitionService->activateCompetition($mock_competition);
        
        // Assert
        $this->assertFalse($result);
        $this->assertStringContainsString(
            trans('messages.validation.fail.activated'),
            Session::get('messages')[0]['message']);
    }

} 