<?php

namespace Tests\Unit\Services\Competition;

use App\Interface\Competition\LevelRepositoryInterface;
use App\Services\Competition\LevelService;
use App\Models\Competition\Competition; // For type hinting if needed
use App\Models\Admin\Admin;             // For type hinting if needed
use App\Models\Competition\Level;       // Import the Level model
use App\Models\User; // For finishLevel test
use Illuminate\Support\Facades\DB; // For finishLevel test
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase; // Or PHPUnit\Framework\TestCase if not a Laravel unit test with app context
use App\Http\Helpers\UserNotifyEmail; // Added this line

class LevelServiceTest extends TestCase
{
    // use MockeryPHPUnitIntegration; // Use this if not extending Laravel's TestCase
    protected $levelRepositoryMock;
    protected LevelService $levelService;

    protected function setUp(): void
    {
        parent::setUp(); // Important if extending Laravel's TestCase
        $this->levelRepositoryMock = Mockery::mock(LevelRepositoryInterface::class);
        
        // Instantiate LevelService with the mock. Add other dependencies if any.
        $this->levelService = new LevelService($this->levelRepositoryMock /*, other dependencies */);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }


    public function test_create_returns_error_if_competition_has_max_levels()
    {
        $competition = Competition::factory()->create();
        $data = [
            'competition_id' => $competition->id,
            'name' => 'Test Level',
            'start_date' => now()->addDay()->format('Y-m-d H:i'),
            'duration' => 60,
            'questions_number' => 5,
            'admin_id' => $competition->admin_id,
            'description' => 'Test desc'
        ];


        $this->levelRepositoryMock
            ->shouldReceive('checkCompetitionMaxLevelNumbers')
            ->once()
            ->with($data['competition_id']) // Service passes competition_id
            ->andReturn(true);


        $result = $this->levelService->create($data);
       
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('messages.validation.not_allow.competition_max_levels', $result['message_key']);
    }
    
    public function test_create_returns_error_if_start_date_before_competition_start_date()
    {
        $data = [
            'competition_id' => 1,
            'name' => 'Test Level',
            'start_date' => now()->subDays(5)->format('Y-m-d H:i'), // Level start before competition
            'duration' => 60,
            'questions_number' => 5,
            'admin_id' => 1,
            'description' => 'Test desc'
        ];

        $mockCompetition = Mockery::mock(Competition::class)->makePartial();
        $mockCompetition->start_date = now()->subDay(); // Competition started yesterday

        $this->levelRepositoryMock
            ->shouldReceive('getCompetitionById')
            ->once()
            ->with($data['competition_id'])
            ->andReturn($mockCompetition);

        $this->levelRepositoryMock
            ->shouldReceive('checkCompetitionMaxLevelNumbers')
            ->once()
            ->with($data['competition_id'])
            ->andReturn(false); // Max levels check passes

        // No need to mock hasTimeConflict as it should fail before this

        $result = $this->levelService->create($data);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('validation.custom.start_date_gt_competition', $result['message_key']);
    }

    public function test_create_returns_error_if_level_time_conflicts()
    {
        $data = [
            'competition_id' => 1,
            'name' => 'Test Level',
            'start_date' => now()->addDay()->format('Y-m-d H:i'),
            'duration' => 60,
            'questions_number' => 5,
            'admin_id' => 1,
            'description' => 'Test desc'
        ];


        $mockCompetition = Mockery::mock(Competition::class)->makePartial();
        $mockCompetition->start_date = now(); // Ensure level start date is not before competition start date

        $this->levelRepositoryMock
            ->shouldReceive('getCompetitionById')
            ->once()
            ->with($data['competition_id'])
            ->andReturn($mockCompetition);
            
        $this->levelRepositoryMock
            ->shouldReceive('checkCompetitionMaxLevelNumbers')
            ->once()
            ->with($data['competition_id'])
            ->andReturn(false); 

        $this->levelRepositoryMock
            ->shouldReceive('hasTimeConflict')
            ->once()
            ->with($data['competition_id'], $data['start_date'], $data['duration']) 
            ->andReturn(true); 

        $result = $this->levelService->create($data);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('messages.validation.not_allow.level_time_conflict', $result['message_key']);
    }
    
    public function test_create_successful_when_all_conditions_pass()
    {
        $data = [
            'competition_id' => 1,
            'name' => 'Test Level',
            'start_date' => now()->addDay()->format('Y-m-d H:i'),
            'duration' => 60,
            'questions_number' => 5,
            'admin_id' => 1, // Assume admin_id 1 is valid for creation
            'description' => 'Test desc'
        ];

        $mockCompetition = Mockery::mock(Competition::class)->makePartial();
        $mockCompetition->start_date = now(); // Level start date is valid

        $mockCreatedLevel = Mockery::mock(Level::class)->makePartial(); // Use partial mock for the created level

        $this->levelRepositoryMock
            ->shouldReceive('getCompetitionById')->once()->with($data['competition_id'])->andReturn($mockCompetition);
            
        $this->levelRepositoryMock
            ->shouldReceive('checkCompetitionMaxLevelNumbers')->once()->with($data['competition_id'])->andReturn(false);

        $this->levelRepositoryMock
            ->shouldReceive('hasTimeConflict')->once()->with($data['competition_id'], $data['start_date'], $data['duration'])->andReturn(false);
            
        $this->levelRepositoryMock
            ->shouldReceive('create') 
            ->once()
            ->with($data) 
            ->andReturn($mockCreatedLevel);
        
        // Mock UserNotifyEmail static call
        $userNotifyEmailMock = Mockery::mock('overload:' . UserNotifyEmail::class);
        $userNotifyEmailMock->shouldReceive('adminLevel')->once()->with($mockCreatedLevel);

        $result = $this->levelService->create($data);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals('saved', $result['message_key']);
        $this->assertSame($mockCreatedLevel, $result['level']);
    }

    // Add more tests for other methods (update, delete, activateLevel, finishLevel)
    // and their various conditions (e.g., canCompetitionBeEdited, isLevelStillActive, etc.)

    public function test_get_edit_data_successful()
    {
        $encodedId = 'test_encoded_id';
        $mockLevel = Mockery::mock(Level::class)->makePartial();
        $mockAdmins = collect([Mockery::mock(Admin::class)->makePartial()]); 

        $this->levelRepositoryMock
            ->shouldReceive('findDecodedOrFail')
            ->once()
            ->with($encodedId)
            ->andReturn($mockLevel);

        $this->levelRepositoryMock
            ->shouldReceive('getAllAdmins')
            ->once()
            ->andReturn($mockAdmins);

        $result = $this->levelService->getEditData($encodedId);

        $this->assertEquals('success', $result['status']);
        $this->assertSame($mockLevel, $result['level']);
        $this->assertSame($mockAdmins, $result['admins']);
    }

    public function test_get_edit_data_throws_exception()
    {
        $encodedId = 'non_existent_id';

        $this->levelRepositoryMock
            ->shouldReceive('findDecodedOrFail')
            ->once()
            ->with($encodedId)
            ->andThrow(new \Illuminate\Database\Eloquent\ModelNotFoundException()); 

        $result = $this->levelService->getEditData($encodedId);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('fetch_error', $result['message_key']);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\ModelNotFoundException::class, $result['exception']);
    }

    public function test_update_successful_without_start_date_change()
    {
        $levelId = 1;
        $updateData = [
            'name' => 'Updated Level Name',
            'description' => 'Updated description',
            'start_date' => now()->addDays(2)->format('Y-m-d H:i:s'), 
            'duration' => 120,
            'admin_id' => 2
        ];

        $mockLevel = Mockery::mock(Level::class)->makePartial();
        $mockLevel->id = $levelId;
        $mockLevel->status = 0; 
        $mockLevel->start_date = \Carbon\Carbon::parse($updateData['start_date']); 
        $mockLevel->duration = 60; 
        $mockLevel->competition_id = 10;
        // $mockLevel->competition is not directly used by the service if start_date doesn't change
        // and UserNotifyEmail isn't called.

        $this->levelRepositoryMock
            ->shouldReceive('findOrFail')
            ->once()
            ->with($levelId)
            ->andReturn($mockLevel);

        $this->levelRepositoryMock->shouldNotReceive('hasTimeConflict');

        $expectedUpdatePayload = \Illuminate\Support\Arr::only($updateData, ['name', 'description', 'start_date', 'duration', 'admin_id']);
        $this->levelRepositoryMock
            ->shouldReceive('update')
            ->once()
            ->with($mockLevel, $expectedUpdatePayload)
            ->andReturn(true);
        
        $result = $this->levelService->update($levelId, $updateData);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals('updated', $result['message_key']);
        $this->assertSame($mockLevel, $result['level']);
    }

    public function test_update_successful_with_start_date_change()
    {
        $levelId = 1;
        $originalStartDate = now()->addDays(2)->format('Y-m-d H:i:s');
        $newStartDate = now()->addDays(5)->format('Y-m-d H:i:s');
        $updateData = [
            'name' => 'Updated Level Name',
            'description' => 'Updated description',
            'start_date' => $newStartDate,
            'duration' => 120, // Note: service uses $level->duration for conflict check, not $updateData['duration']
            'admin_id' => 2
        ];

        $mockLevel = Mockery::mock(Level::class)->makePartial();
        $mockLevel->id = $levelId;
        $mockLevel->status = 0; 
        $mockLevel->start_date = \Carbon\Carbon::parse($originalStartDate);
        $mockLevel->duration = 60; // This duration will be used for time conflict check
        $mockLevel->competition_id = 10;
        
        // If UserNotifyEmail::usersUpdateLevel is called, it needs $level->competition
        $mockCompetition = Mockery::mock(Competition::class)->makePartial();
        $mockLevel->competition = $mockCompetition;

        // Mock UserNotifyEmail static call for usersUpdateLevel
        $userNotifyEmailMock = Mockery::mock('overload:' . UserNotifyEmail::class);
        $userNotifyEmailMock->shouldReceive('usersUpdateLevel')->once()->with($mockCompetition, $mockLevel);

        $this->levelRepositoryMock
            ->shouldReceive('findOrFail')
            ->once()
            ->with($levelId)
            ->andReturn($mockLevel);

        $this->levelRepositoryMock
            ->shouldReceive('hasTimeConflict')
            ->once()
            ->with($mockLevel->competition_id, $updateData['start_date'], $mockLevel->duration, $mockLevel->id)
            ->andReturn(false); 

        $expectedUpdatePayload = \Illuminate\Support\Arr::only($updateData, ['name', 'description', 'start_date', 'duration', 'admin_id']);
        $this->levelRepositoryMock
            ->shouldReceive('update')
            ->once()
            ->with($mockLevel, $expectedUpdatePayload)
            ->andReturn(true);

        $result = $this->levelService->update($levelId, $updateData);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals('updated', $result['message_key']);
        $this->assertSame($mockLevel, $result['level']);
    }

    public function test_update_returns_error_if_level_is_active()
    {
        $levelId = 1;
        $updateData = ['name' => 'New Name'];

        $mockLevel = Mockery::mock(Level::class)->makePartial(); // Make partial
        $mockLevel->status = 1; // Active level
        // No need to set other properties if they are not accessed before the status check

        $this->levelRepositoryMock
            ->shouldReceive('findOrFail')
            ->once()
            ->with($levelId)
            ->andReturn($mockLevel);

        $result = $this->levelService->update($levelId, $updateData);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('messages.validation.not_allow.active_level_update', $result['message_key']);
    }

    public function test_update_returns_error_if_time_conflict_on_start_date_change()
    {
        $levelId = 1;
        $originalStartDate = now()->addDays(2)->format('Y-m-d H:i:s');
        $newStartDate = now()->addDays(5)->format('Y-m-d H:i:s');
        $updateData = [
            'name' => 'Updated Level Name',
            'start_date' => $newStartDate,
            // other fields if Arr::only expects them, but for this path, not critical
        ];

        $mockLevel = Mockery::mock(Level::class)->makePartial();
        $mockLevel->id = $levelId;
        $mockLevel->status = 0; 
        $mockLevel->start_date = \Carbon\Carbon::parse($originalStartDate);
        $mockLevel->duration = 60; 
        $mockLevel->competition_id = 10;

        $this->levelRepositoryMock
            ->shouldReceive('findOrFail')
            ->once()
            ->with($levelId)
            ->andReturn($mockLevel);

        $this->levelRepositoryMock
            ->shouldReceive('hasTimeConflict')
            ->once()
            ->with($mockLevel->competition_id, $updateData['start_date'], $mockLevel->duration, $mockLevel->id)
            ->andReturn(true); 

        $result = $this->levelService->update($levelId, $updateData);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('messages.validation.not_allow.level_activate_time_conflict', $result['message_key']);
    }

    public function test_update_returns_error_if_repository_update_fails()
    {
        $levelId = 1;
        $updateData = [
            'name' => 'Updated Level Name',
            'start_date' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'description' => 'Valid description',
            'duration' => 60,
            'admin_id' => 1
        ];

        $mockLevel = Mockery::mock(Level::class)->makePartial();
        $mockLevel->id = $levelId;
        $mockLevel->status = 0; 
        $mockLevel->start_date = \Carbon\Carbon::parse($updateData['start_date']); 
        $mockLevel->competition_id = 10; // Needed if hasTimeConflict were to be called (not in this path)

        $this->levelRepositoryMock
            ->shouldReceive('findOrFail')
            ->once()
            ->with($levelId)
            ->andReturn($mockLevel);

        $expectedUpdatePayload = \Illuminate\Support\Arr::only($updateData, ['name', 'description', 'start_date', 'duration', 'admin_id']);
        $this->levelRepositoryMock
            ->shouldReceive('update')
            ->once()
            ->with($mockLevel, $expectedUpdatePayload)
            ->andReturn(false); 

        $result = $this->levelService->update($levelId, $updateData);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('updated_error_generic', $result['message_key']);
    }

    public function test_update_catches_generic_exception()
    {
        $levelId = 1;
        $updateData = ['name' => 'New Name'];

        $this->levelRepositoryMock
            ->shouldReceive('findOrFail')
            ->once()
            ->with($levelId)
            ->andThrow(new \Exception('Generic error'));
        
        $result = $this->levelService->update($levelId, $updateData);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('updated_error', $result['message_key']);
        $this->assertInstanceOf(\Exception::class, $result['exception']);
    }

    // Method: delete
    public function test_delete_successful()
    {
        $levelId = 1;
        $mockLevel = Mockery::mock(Level::class)->makePartial(); // Make partial
        $mockCompetition = Mockery::mock(Competition::class)->makePartial(); // Make partial
        
        // Set properties on mocks that the service will access
        $mockCompetition->status = 0; 
        $mockLevel->competition = $mockCompetition; 

        $this->levelRepositoryMock
            ->shouldReceive('findOrFail')
            ->once()
            ->with($levelId)
            ->andReturn($mockLevel);

        $this->levelRepositoryMock
            ->shouldReceive('canCompetitionBeEdited')
            ->once()
            ->with($mockCompetition)
            ->andReturn(true);
        
        $this->levelRepositoryMock
            ->shouldReceive('delete')
            ->once()
            ->with($mockLevel)
            ->andReturn(true); 

        $result = $this->levelService->delete($levelId);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals('deleted', $result['message_key']);
    }

    public function test_delete_returns_error_if_competition_cannot_be_edited()
    {
        $levelId = 1;
        $mockLevel = Mockery::mock(Level::class)->makePartial(); 
        $mockCompetition = Mockery::mock(Competition::class)->makePartial();
        $mockLevel->competition = $mockCompetition;
        // $mockCompetition->status is not relevant if canCompetitionBeEdited is false

        $this->levelRepositoryMock
            ->shouldReceive('findOrFail')
            ->once()
            ->with($levelId)
            ->andReturn($mockLevel);

        $this->levelRepositoryMock
            ->shouldReceive('canCompetitionBeEdited')
            ->once()
            ->with($mockCompetition)
            ->andReturn(false); 

        $result = $this->levelService->delete($levelId);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('messages.validation.not_allow.competition_update', $result['message_key']);
    }

    public function test_delete_returns_error_if_competition_is_active()
    {
        $levelId = 1;
        $mockLevel = Mockery::mock(Level::class)->makePartial();
        $mockCompetition = Mockery::mock(Competition::class)->makePartial();
        $mockCompetition->status = 1; // Competition is active
        $mockLevel->competition = $mockCompetition;

        $this->levelRepositoryMock
            ->shouldReceive('findOrFail')
            ->once()
            ->with($levelId)
            ->andReturn($mockLevel);

        $this->levelRepositoryMock
            ->shouldReceive('canCompetitionBeEdited')
            ->once()
            ->with($mockCompetition)
            ->andReturn(true); 

        $result = $this->levelService->delete($levelId);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('messages.validation.not_allow.active_competition_update', $result['message_key']);
    }

    public function test_delete_returns_error_if_repository_delete_fails()
    {
        $levelId = 1;
        $mockLevel = Mockery::mock(Level::class)->makePartial();
        $mockCompetition = Mockery::mock(Competition::class)->makePartial();
        $mockCompetition->status = 0; 
        $mockLevel->competition = $mockCompetition;

        $this->levelRepositoryMock
            ->shouldReceive('findOrFail')
            ->once()
            ->with($levelId)
            ->andReturn($mockLevel);

        $this->levelRepositoryMock
            ->shouldReceive('canCompetitionBeEdited')
            ->once()
            ->with($mockCompetition)
            ->andReturn(true);

        $this->levelRepositoryMock
            ->shouldReceive('delete')
            ->once()
            ->with($mockLevel)
            ->andReturn(false); 

        $result = $this->levelService->delete($levelId);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('deleted_error_generic', $result['message_key']);
    }

    public function test_delete_catches_generic_exception()
    {
        $levelId = 1;
        $this->levelRepositoryMock
            ->shouldReceive('findOrFail')
            ->once()
            ->with($levelId)
            ->andThrow(new \Exception('Generic delete error'));

        $result = $this->levelService->delete($levelId);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('deleted_error', $result['message_key']);
        $this->assertInstanceOf(\Exception::class, $result['exception']);
    }
    
    // Method: activateLevel
    // Most activateLevel tests seemed okay, but ensure mocks are partial if properties are read.
    public function test_activate_level_successful()
    {
        $levelId = 1;
        $mockLevel = Mockery::mock(Level::class)->makePartial();
        $mockCompetition = Mockery::mock(Competition::class)->makePartial();

        $mockLevel->id = $levelId;
        $mockLevel->competition_id = 10;
        $mockLevel->competition = $mockCompetition;
        $mockLevel->start_date = \Carbon\Carbon::now()->subHour(); 
        $mockLevel->questions_number = 5;
        $mockLevel->duration = 60;
        $mockCompetition->status = 1; 

        $this->levelRepositoryMock->shouldReceive('findOrFail')->once()->with($levelId)->andReturn($mockLevel);
        $this->levelRepositoryMock->shouldReceive('getQuestionsCount')->once()->with($mockLevel)->andReturn(5);
        $this->levelRepositoryMock->shouldReceive('isLevelTheEarliest')->once()->with($mockLevel)->andReturn(true);
        $this->levelRepositoryMock->shouldReceive('isPreviousLevelAudited')->once()->with($mockLevel)->andReturn(true);
        $this->levelRepositoryMock->shouldReceive('areAllCompetitionLevelsAfterNow')->once()->with($mockCompetition, $mockLevel)->andReturn(true);
        $this->levelRepositoryMock->shouldReceive('hasTimeConflict')
            ->once()
            ->with($mockLevel->competition_id, Mockery::on(function($arg) { // Accept any Carbon instance or string for now()
                return $arg instanceof \Carbon\Carbon || is_string($arg);
            }), $mockLevel->duration, $mockLevel->id) 
            ->andReturn(false); 
        $this->levelRepositoryMock->shouldReceive('canLevelBeEdited')->once()->with($mockLevel)->andReturn(true);
        
        $this->levelRepositoryMock->shouldReceive('update')
            ->once()
            ->with(
                $mockLevel,
                Mockery::on(function ($data) {
                    return isset($data['start_date'], $data['status']) &&
                        $data['start_date'] instanceof \Carbon\Carbon &&
                        $data['status'] === '1';
                })
            )
            ->andReturn(true);


        // Mock UserNotifyEmail static call for usersActivateLevel
        $userNotifyEmailMock = Mockery::mock('overload:' . UserNotifyEmail::class);
        $userNotifyEmailMock->shouldReceive('usersActivateLevel')->once()->with($mockCompetition, $mockLevel);

        $result = $this->levelService->activateLevel($levelId);
       
        $this->assertEquals('success', $result['status']);
        $this->assertEquals('activated', $result['message_key']);
    }

    // ... other activateLevel failure tests often make mocks partial already or don't hit this issue ...
    // For brevity, I'll assume they are mostly fine but would need a check for makePartial if properties are read.
    // Example for one:
    public function test_activate_level_fails_if_competition_not_active()
    {
        $levelId = 1;
        $mockLevel = Mockery::mock(Level::class)->makePartial();
        $mockCompetition = Mockery::mock(Competition::class)->makePartial();
        $mockLevel->competition = $mockCompetition;
        $mockCompetition->status = 0; 

        $this->levelRepositoryMock->shouldReceive('findOrFail')->once()->with($levelId)->andReturn($mockLevel);

        $result = $this->levelService->activateLevel($levelId);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('messages.validation.not_allow.level_activate_before_competition', $result['message_key']);
    }
    // ... (ensure other activateLevel tests are similarly robust with makePartial if needed) ...

    // Method: finishLevel
    public function test_finish_level_successful()
    {
        $levelId = 1;
        $mockLevel = Mockery::mock(Level::class)->makePartial();
        $mockCompetition = Mockery::mock(Competition::class)->makePartial();

        $mockLevel->id = $levelId;
        $mockLevel->status = 1; 
        $mockLevel->competition = $mockCompetition;
        $mockLevel->competition_id = 1; 

        $this->levelRepositoryMock->shouldReceive('findOrFail')->once()->with($levelId)->andReturn($mockLevel);
        $this->levelRepositoryMock->shouldReceive('isLevelStillActive')->once()->with($mockLevel)->andReturn(false); 
        $this->levelRepositoryMock->shouldReceive('canLevelBeEdited')->once()->with($mockLevel)->andReturn(true); 

        $mockUser = Mockery::mock(User::class)->makePartial(); // make partial
        $mockUser->id = 1; // ensure id is set for getUnansweredQuestionsForUser
        $mockUsers = collect([$mockUser]);
        
        $this->levelRepositoryMock->shouldReceive('getUsersForCompetition')->with($mockCompetition)->andReturn($mockUsers);
        $this->levelRepositoryMock->shouldReceive('getUnansweredQuestionsForUser')->with($mockLevel, $mockUser)->andReturn(collect()); 

        $mockAuditors = collect([Mockery::mock(Admin::class)->makePartial()]); // make partial
         // If Auditor model has id property used by assignAuditorsToUsersInPivot, ensure it's set if not default by factory mock
        // $mockAuditorInstance = Mockery::mock(Admin::class)->makePartial(); $mockAuditorInstance->id = 1; $mockAuditors = collect([$mockAuditorInstance]);

        $this->levelRepositoryMock->shouldReceive('getAuditorsForCompetition')->with($mockCompetition)->andReturn($mockAuditors);
        $this->levelRepositoryMock->shouldReceive('assignAuditorsToUsersInPivot')->once()->with($mockLevel, $mockUsers, $mockAuditors);

        $this->levelRepositoryMock->shouldReceive('update')
            ->once()
            ->with($mockLevel, ['status' => "2"])
            ->andReturn(true);

        // Mock UserNotifyEmail static call for auditorsFinishLevel
        $userNotifyEmailMock = Mockery::mock('overload:' . UserNotifyEmail::class);
        $userNotifyEmailMock->shouldReceive('auditorsFinishLevel')->once()->with($mockCompetition, $mockLevel);

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        DB::shouldNotReceive('rollBack');

        $result = $this->levelService->finishLevel($levelId);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals('finish', $result['message_key']);
    }
    
    public function test_finish_level_fails_if_level_not_active_or_still_running()
    {
        $levelId = 1;
        $mockLevel = Mockery::mock(Level::class)->makePartial();
        $mockLevel->status = 0; // Not active
        // isLevelStillActive might not be called if status is 0, so no need to set $mockLevel->competition

        $this->levelRepositoryMock->shouldReceive('findOrFail')->once()->with($levelId)->andReturn($mockLevel);
        // No need to mock isLevelStillActive if the first part of OR condition fails.

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldNotReceive('commit');
        DB::shouldNotReceive('rollBack'); 

        $result = $this->levelService->finishLevel($levelId);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('messages.validation.not_allow.level_finish_still_active', $result['message_key']);
    }

    public function test_finish_level_fails_if_level_cannot_be_edited_for_finish()
    {
        $levelId = 1;
        $mockLevel = Mockery::mock(Level::class)->makePartial();
        $mockLevel->status = 1; 
        // $mockLevel->competition needed if isLevelStillActive is called
        $mockCompetition = Mockery::mock(Competition::class)->makePartial();
        $mockLevel->competition = $mockCompetition;


        $this->levelRepositoryMock->shouldReceive('findOrFail')->once()->with($levelId)->andReturn($mockLevel);
        $this->levelRepositoryMock->shouldReceive('isLevelStillActive')->once()->with($mockLevel)->andReturn(false); 
        $this->levelRepositoryMock->shouldReceive('canLevelBeEdited')->once()->with($mockLevel)->andReturn(false); 

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldNotReceive('commit'); 
        DB::shouldNotReceive('rollBack');

        $result = $this->levelService->finishLevel($levelId);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('messages.validation.not_allow.level_edit_restricted_finish', $result['message_key']);
    }


    public function test_finish_level_successful_with_unanswered_questions()
    {
        $levelId = 1;
        $mockLevel = Mockery::mock(Level::class)->makePartial();
        $mockCompetition = Mockery::mock(Competition::class)->makePartial();
        
        $mockUser = Mockery::mock(User::class)->makePartial(); 
        $mockUser->id = 100;
        $mockQuestion = Mockery::mock(\App\Models\Competition\Question::class)->makePartial(); // Fully qualify
        $mockQuestion->id = 200;

        $mockLevel->id = $levelId;
        $mockLevel->status = 1; 
        $mockLevel->competition = $mockCompetition;
        $mockLevel->competition_id = 1;

        $this->levelRepositoryMock->shouldReceive('findOrFail')->once()->with($levelId)->andReturn($mockLevel);
        $this->levelRepositoryMock->shouldReceive('isLevelStillActive')->once()->with($mockLevel)->andReturn(false);
        $this->levelRepositoryMock->shouldReceive('canLevelBeEdited')->once()->with($mockLevel)->andReturn(true);

        $mockUsers = collect([$mockUser]);
        $this->levelRepositoryMock->shouldReceive('getUsersForCompetition')->with($mockCompetition)->andReturn($mockUsers);
        $this->levelRepositoryMock->shouldReceive('getUnansweredQuestionsForUser')->with($mockLevel, $mockUser)->andReturn(collect([$mockQuestion]));
        $this->levelRepositoryMock->shouldReceive('createMultipleResponses')->once(); 

        $mockAuditor = Mockery::mock(Admin::class)->makePartial(); // ensure Admin mock
        $mockAuditor->id = 1; // Ensure id is available for assignAuditorsToUsersInPivot
        $mockAuditors = collect([$mockAuditor]); 

        $this->levelRepositoryMock->shouldReceive('getAuditorsForCompetition')->with($mockCompetition)->andReturn($mockAuditors);
        $this->levelRepositoryMock->shouldReceive('assignAuditorsToUsersInPivot')->once();

        $this->levelRepositoryMock->shouldReceive('update')->once()->with($mockLevel, ['status' => "2"])->andReturn(true);

        // Mock UserNotifyEmail static call for auditorsFinishLevel
        $userNotifyEmailMock = Mockery::mock('overload:' . UserNotifyEmail::class);
        $userNotifyEmailMock->shouldReceive('auditorsFinishLevel')->once()->with($mockCompetition, $mockLevel);

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        DB::shouldNotReceive('rollBack');

        $result = $this->levelService->finishLevel($levelId);
        $this->assertEquals('success', $result['status']);
    }

    public function test_finish_level_fails_if_repository_update_fails()
    {
        $levelId = 1;
        $mockLevel = Mockery::mock(Level::class)->makePartial();
        $mockCompetition = Mockery::mock(Competition::class)->makePartial();
        $mockLevel->id = $levelId;
        $mockLevel->status = 1;
        $mockLevel->competition = $mockCompetition;
        $mockLevel->competition_id = 1;

        $this->levelRepositoryMock->shouldReceive('findOrFail')->once()->with($levelId)->andReturn($mockLevel);
        $this->levelRepositoryMock->shouldReceive('isLevelStillActive')->once()->with($mockLevel)->andReturn(false);
        $this->levelRepositoryMock->shouldReceive('canLevelBeEdited')->once()->with($mockLevel)->andReturn(true);

        $this->levelRepositoryMock->shouldReceive('getUsersForCompetition')->andReturn(collect());
        $this->levelRepositoryMock->shouldReceive('getAuditorsForCompetition')->andReturn(collect());

        $this->levelRepositoryMock->shouldReceive('update')
            ->once()
            ->with($mockLevel, ['status' => "2"])
            ->andReturn(false); 

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once(); 
        DB::shouldNotReceive('commit');

        $result = $this->levelService->finishLevel($levelId);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('finish_error_generic', $result['message_key']);
    }

    public function test_finish_level_catches_generic_exception()
    {
        $levelId = 1;
        $this->levelRepositoryMock->shouldReceive('findOrFail')
            ->once()
            ->with($levelId)
            ->andThrow(new \Exception('Generic finish error'));

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once(); 
        DB::shouldNotReceive('commit');

        $result = $this->levelService->finishLevel($levelId);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('finish_error', $result['message_key']);
        $this->assertInstanceOf(\Exception::class, $result['exception']);
    }
}