<?php

namespace Tests\Unit;

use Bus;
use Mockery;
use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Admin\Admin;
use App\Helpers\UserNotifyEmail;
use App\Models\Competition\Level;
use Illuminate\Support\Collection;
use App\Contracts\FlasherInterface;
use Illuminate\Support\Facades\Auth;
use App\Models\Competition\Competition;
use App\Jobs\Competition\FinishLevelJob;
use App\Services\Competition\LevelService;
use App\Contracts\TransactionManagerInterface;
use App\Interface\Competition\LevelRepositoryInterface;
use App\Services\Notification\OptimizedCompetitionNotificationService;

class LevelServiceTest extends TestCase
{
    /** @var LevelService&\Mockery\MockInterface */
    protected $levelService;
    /** @var LevelRepositoryInterface&\Mockery\MockInterface */
    protected $levelRepository;
    /** @var TransactionManagerInterface&\Mockery\MockInterface */
    protected $transactionManager;
    /** @var FlasherInterface&\Mockery\MockInterface */
    protected $flasher;
    /** @var OptimizedCompetitionNotificationService&\Mockery\MockInterface */
    protected $notificationService;
    /** @var Level|\Mockery\MockInterface */
    protected $level_partial;
    /** @var Competition|\Mockery\MockInterface */
    protected $competition_partial;
    /** @var UserNotifyEmail|\Mockery\MockInterface */
    protected $userNotifyEmail;


    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mock repository & transaction manager & flasher
        $this->levelRepository = Mockery::mock(LevelRepositoryInterface::class);
        $this->transactionManager = Mockery::mock(TransactionManagerInterface::class);
        $this->flasher = Mockery::mock(FlasherInterface::class);
        $this->notificationService = Mockery::mock(OptimizedCompetitionNotificationService::class);
        $this->levelService = new LevelService(
            $this->levelRepository,
            $this->transactionManager,
            $this->flasher,
            $this->notificationService
        );

        $this->level_partial = Mockery::mock(Level::class)->makePartial();
        $this->level_partial->id = 1;
        
        $this->competition_partial = Mockery::mock(Competition::class)->makePartial();
        $this->competition_partial->id = 1;

        // mock UserNotifyEmail
        $this->userNotifyEmail = Mockery::mock('alias:'.UserNotifyEmail::class);
        
        // Mock Auth facade
        Auth::shouldReceive('id')->andReturn(1);
        Auth::shouldReceive('user')->andReturn($this->mockAdmin('owner', true));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function createLevelData(array $overrides = [],bool $strDate = true): array
    {
        if(array_key_exists('start_date',$overrides)){
            $startDate = $overrides['start_date']->format('Y-m-d H:i');
            unset($overrides['start_date']);
        }else{
            $startDate = now()->addDay()->format('Y-m-d H:i');
        }
        return array_merge([
            'name' => 'Test Level',
            'description' => 'Test Description',
            'admin_id' => 1,
            'start_date' => $strDate ? $startDate : Carbon::parse($startDate),
            'duration' => 60,
            'questions_number' => 10,
            'status' => 'pending', // inactive
        ], $overrides);
    
    }

    private function mockAdmin(string $role, bool $hasRole): Admin
    {
        $admin = Mockery::mock(Admin::class);
        $admin->shouldReceive('hasRole')->with($role)->andReturn($hasRole);
        return $admin;
    }

    public function test_create_level_success()
    {
        // Arrange
        $data = $this->createLevelData(strDate: true);
        $competition = $this->competition_partial;
        $level = $this->level_partial;
    
        $competition->shouldReceive('hasReachedMaxLevels')->once()->andReturn(false);
        $competition->shouldReceive('getAttribute')->with('start_date')->andReturn(now()->subDay());

        $this->levelRepository
            ->shouldReceive('isAdminAllowedToBeLevelManager')
            ->with($data['admin_id'],)
            ->once()
            ->andReturn(true);
        

        $this->levelRepository
            ->shouldReceive('hasTimeConflict')
            ->with($competition->id, $data['start_date'], $data['duration'])
            ->once()
            ->andReturn(false);
            
        $this->levelRepository
            ->shouldReceive('create')
            ->with(array_merge($data, ['competition_id' => $competition->id]))
            ->once()
            ->andReturn($level);

        $this->userNotifyEmail
            ->shouldReceive('adminLevel')
            ->with($level)
            ->once();

        $this->notificationService
            ->shouldReceive('levelCreated')
            ->with($competition, $level)
            ->once();
            
        $this->flasher
            ->shouldReceive('crudSuccess')
            ->with('saved')
            ->once();
            
        // Act
        $result = $this->levelService->create($data, $competition);
        
        // Assert
        $this->assertTrue($result);
    }

    public function test_create_level_faild_manager_admin_not_aviable()
    {
        // Arrange
        $data = $this->createLevelData(strDate: true);
        $competition = $this->competition_partial;
    

        $this->levelRepository
            ->shouldReceive('isAdminAllowedToBeLevelManager')
            ->with($data['admin_id'],)
            ->once()
            ->andReturn(false);
            
        // Act
        $result = $this->levelService->create($data, $competition);
        
        // Assert
        $this->assertFalse($result);
    }

    public function test_create_level_max_levels_reached()
    {
        // Arrange
        $data = $this->createLevelData(strDate: true);
        $competition = $this->competition_partial;
        
        $competition->shouldReceive('hasReachedMaxLevels')->once()->andReturn(true);
        $this->levelRepository
            ->shouldReceive('isAdminAllowedToBeLevelManager')
            ->with($data['admin_id'],)
            ->once()
            ->andReturn(true);    
        $this->flasher
            ->shouldReceive('error')
            ->with(__('messages.validation.not_allow.competition_max_levels'))
            ->once();
            
        // Act
        $result = $this->levelService->create($data, $competition);
        
        // Assert
        $this->assertFalse($result);
    }

    public function test_create_level_time_conflict()
    {
        // Arrange
        $data = $this->createLevelData(strDate: true);
        $competition = $this->competition_partial;
        
        $competition->shouldReceive('hasReachedMaxLevels')->once()->andReturn(false);
        $competition->shouldReceive('getAttribute')->with('start_date')->andReturn(now()->subDay());
        $this->levelRepository
            ->shouldReceive('isAdminAllowedToBeLevelManager')
            ->with($data['admin_id'],)
            ->once()
            ->andReturn(true);            
        $this->levelRepository
            ->shouldReceive('hasTimeConflict')
            ->with($competition->id, $data['start_date'], $data['duration'])
            ->once()
            ->andReturn(true);
            
        $this->flasher
            ->shouldReceive('error')
            ->with(__('messages.validation.not_allow.level_time_conflict'))
            ->once();
            
        // Act
        $result = $this->levelService->create($data, $competition);
        
        // Assert
        $this->assertFalse($result);
    }

    public function test_create_level_start_date_gt_competition()
    {
        // Arrange
        $data = $this->createLevelData(strDate: true);
        $competition = $this->competition_partial;
        
        $competition->shouldReceive('hasReachedMaxLevels')->once()->andReturn(false);
        $competition->shouldReceive('getAttribute')->with('start_date')->andReturn(now()->addDay());
        $this->levelRepository
            ->shouldReceive('isAdminAllowedToBeLevelManager')
            ->with($data['admin_id'],)
            ->once()
            ->andReturn(true);          
        $this->flasher
            ->shouldReceive('error')
            ->with(__('validation.custom.start_date_gt_competition'))
            ->once();

        // Act
        $result = $this->levelService->create($data, $competition);

        // Assert
        $this->assertFalse($result);
    }

    public function test_update_level_success_without_start_date_change()
    {
        // Arrange
        $competition = $this->competition_partial;
        $data = $this->createLevelData();
        $level = $this->level_partial;
        foreach($data as $key => $value){
            $level->shouldReceive('getAttribute')->with($key)->andReturn($value);
        }
        
        $level->shouldReceive('getAttribute')->with('competition')->andReturn($competition);
        
        $competition->shouldReceive('getAttribute')->with('status')->andReturn('pending');
        $competition->shouldReceive('getAttribute')->with('start_date')->andReturn(now()->subDay());
        
        $updatedData = [
            'name' => 'new name',
            'description' => 'new description',
            'start_date' => $data['start_date'],
            'duration' => 60,
            'admin_id' => 1,
        ];
        $this->levelRepository
            ->shouldReceive('isAdminAllowedToBeLevelManager')
            ->with($data['admin_id'],)
            ->once()
            ->andReturn(true);              
        $this->levelRepository
            ->shouldReceive('update')
            ->with($level, Mockery::any())
            ->once()
            ->andReturn(true);

        $this->notificationService
            ->shouldReceive('levelUpdated')
            ->with($competition, $level)
            ->once();
            
        $this->flasher
            ->shouldReceive('crudSuccess')
            ->with('updated')
            ->once();
            
        // Act
        $result = $this->levelService->update($level, $updatedData);
        
        // Assert
        $this->assertTrue($result);
    }

    public function test_update_level_success_with_start_date_change()
    {
        // Arrange
        $competition = $this->competition_partial;
        $data = $this->createLevelData();
        $level = $this->level_partial;
        $level->competition_id = 1;
        foreach($data as $key => $value){
            $level->shouldReceive('getAttribute')->with($key)->andReturn($value);
        }
        
        $level->shouldReceive('getAttribute')->with('competition')->andReturn($competition);
        
        $competition->shouldReceive('getAttribute')->with('status')->andReturn('pending');
        $competition->shouldReceive('getAttribute')->with('start_date')->andReturn(now()->subDay());
        
        $updatedData = [
            'name' => 'new name',
            'description' => 'new description',
            'start_date' => now()->addDay(2)->format('Y-m-d H:i'),
            'duration' => 60,
            'admin_id' => 1,
        ];
        $this->levelRepository
            ->shouldReceive('hasTimeConflict')
            ->with($competition->id, $updatedData['start_date'], $updatedData['duration'], $level->id)
            ->once()
            ->andReturn(false);
        $this->levelRepository
            ->shouldReceive('isAdminAllowedToBeLevelManager')
            ->with($data['admin_id'],)
            ->once()
            ->andReturn(true);  
        $this->levelRepository
            ->shouldReceive('update')
            ->with($level, Mockery::any())
            ->once()
            ->andReturn(true);

        $this->userNotifyEmail
            ->shouldReceive('usersUpdateLevel')
            ->with($competition, $level)
            ->once();

        $this->notificationService
            ->shouldReceive('levelUpdated')
            ->with($competition, $level)
            ->once();

        $this->flasher
            ->shouldReceive('crudSuccess')
            ->with('updated')
            ->once();
            
        // Act
        $result = $this->levelService->update($level, $updatedData);
        
        // Assert
        $this->assertTrue($result);
    }

    public function test_update_level_active_level()
    {
        // Arrange
        $competition = $this->competition_partial;
        $data = $this->createLevelData();
        $level = $this->level_partial;
        
        $level->shouldReceive('getAttribute')->with('status')->andReturn('active');
        $level->shouldReceive('getAttribute')->with('competition')->andReturn($competition);
        
        $competition->shouldReceive('getAttribute')->with('status')->andReturn('pending');
        $this->levelRepository
            ->shouldReceive('isAdminAllowedToBeLevelManager')
            ->with($data['admin_id'],)
            ->once()
            ->andReturn(true);   

        $this->flasher
            ->shouldReceive('error')
            ->with(__('messages.validation.not_allow.active_level_update'))
            ->once();
            
        // Act
        $result = $this->levelService->update($level, $data);
        
        // Assert
        $this->assertFalse($result);
    }

    public function test_update_level_active_competition()
    {
        // Arrange
        $competition = $this->competition_partial;
        $data = $this->createLevelData();
        $level = $this->level_partial;  

        $level->shouldReceive('getAttribute')->with('status')->andReturn('pending');
        $level->shouldReceive('getAttribute')->with('competition')->andReturn($competition);
        
        $competition->shouldReceive('getAttribute')->with('status')->andReturn('active');    
        $this->levelRepository
            ->shouldReceive('isAdminAllowedToBeLevelManager')
            ->with($data['admin_id'],)
            ->once()
            ->andReturn(true);          
        $this->flasher
            ->shouldReceive('error')
            ->with(__('messages.validation.not_allow.active_competition_update'))
            ->once();
            
        // Act  
        $result = $this->levelService->update($level, $data);
        
        // Assert
        $this->assertFalse($result);
    }

    public function test_update_level_new_start_date_less_than_competition()
    {
        // Arrange
        $competition = $this->competition_partial;
        $data = $this->createLevelData();
        $level = $this->level_partial;
        $level->competition_id = 1;
        foreach($data as $key => $value){
            $level->shouldReceive('getAttribute')->with($key)->andReturn($value);
        }

        $level->shouldReceive('getAttribute')->with('competition')->andReturn($competition);
        
        $competition->shouldReceive('getAttribute')->with('status')->andReturn('pending');
        $competition->shouldReceive('getAttribute')->with('start_date')->andReturn(now()->subDay());
        
        $updatedData = [
            'name' => 'new name',
            'description' => 'new description',
            'start_date' => now()->subDay(2)->format('Y-m-d H:i'),
            'duration' => 60,
            'admin_id' => 1,
        ];

        $this->levelRepository
            ->shouldReceive('isAdminAllowedToBeLevelManager')
            ->with($data['admin_id'],)
            ->once()
            ->andReturn(true);          
        $this->levelRepository
            ->shouldReceive('hasTimeConflict')
            ->with($competition->id, $updatedData['start_date'], $updatedData['duration'], $level->id)
            ->once()
            ->andReturn(false);

        $this->flasher
            ->shouldReceive('error')
            ->with(__('validation.custom.start_date_gt_competition'))
            ->once();
            
        // Act
        $result = $this->levelService->update($level, $updatedData);
        
        // Assert
        $this->assertFalse($result);    
    }

    public function test_update_level_new_start_date_has_time_conflict()
    {
        // Arrange
        $competition = $this->competition_partial;
        $data = $this->createLevelData();
        $level = $this->level_partial;
        $level->competition_id = 1;
        foreach($data as $key => $value){
            $level->shouldReceive('getAttribute')->with($key)->andReturn($value);
        }

        $level->shouldReceive('getAttribute')->with('competition')->andReturn($competition);
        
        $competition->shouldReceive('getAttribute')->with('status')->andReturn('pending');
        $competition->shouldReceive('getAttribute')->with('start_date')->andReturn(now()->subDay());
        
        $updatedData = [
            'name' => 'new name',
            'description' => 'new description',
            'start_date' => now()->addDay(2)->format('Y-m-d H:i'),
            'duration' => 60,
            'admin_id' => 1,
        ];

        $this->levelRepository
            ->shouldReceive('isAdminAllowedToBeLevelManager')
            ->with($data['admin_id'],)
            ->once()
            ->andReturn(true);  
        $this->levelRepository
            ->shouldReceive('hasTimeConflict')
            ->with($competition->id, $updatedData['start_date'], $updatedData['duration'], $level->id)
            ->once()
            ->andReturn(true);

        $this->flasher
            ->shouldReceive('error')
            ->with(__('messages.validation.not_allow.level_activate_time_conflict'))
            ->once();
            
        // Act
        $result = $this->levelService->update($level, $updatedData);
        
        // Assert
        $this->assertFalse($result);    
    }

    public function test_update_level_faild_admin_manager_not_aviable()
    {
        // Arrange
        $competition = $this->competition_partial;
        $data = $this->createLevelData();
        $level = $this->level_partial;
        $level->competition_id = 1;
        foreach($data as $key => $value){
            $level->shouldReceive('getAttribute')->with($key)->andReturn($value);
        }

        $level->shouldReceive('getAttribute')->with('competition')->andReturn($competition);
        
        
        $updatedData = [
            'name' => 'new name',
            'description' => 'new description',
            'start_date' => now()->addDay(2)->format('Y-m-d H:i'),
            'duration' => 60,
            'admin_id' => 1,
        ];

        $this->levelRepository
            ->shouldReceive('isAdminAllowedToBeLevelManager')
            ->with($data['admin_id'],)
            ->once()
            ->andReturn(false);  
            
        // Act
        $result = $this->levelService->update($level, $updatedData);
        
        // Assert
        $this->assertFalse($result);    
    }
    
    
    public function test_delete_level_success()
    {
        // Arrange
        $level = $this->level_partial;
        $competition = $this->competition_partial;
        
        $level->shouldReceive('getAttribute')->with('competition')->andReturn($competition);
        $level->shouldReceive('canEdit')->andReturn(true);
        
        $competition->shouldReceive('getAttribute')->with('status')->andReturn('pending');
        
        $this->levelRepository
            ->shouldReceive('delete')
            ->with($level)
            ->once()
            ->andReturn(true);
            
        $this->flasher
            ->shouldReceive('crudSuccess')
            ->with('deleted')
            ->once();
            
        // Act
        $result = $this->levelService->delete($level);
        
        // Assert
        $this->assertTrue($result);
    }

    public function test_delete_level_faild_active_competition()
    {
        // Arrange
        $level = $this->level_partial;
        $competition = $this->competition_partial;
        
        $level->shouldReceive('getAttribute')->with('competition')->andReturn($competition);
        $level->shouldReceive('canEdit')->andReturn(true);
        
        $competition->shouldReceive('getAttribute')->with('status')->andReturn('active');
        
        $this->flasher
            ->shouldReceive('error')
            ->with(__('messages.validation.not_allow.active_competition_update'))
            ->once();
            
        // Act
        $result = $this->levelService->delete($level);
        
        // Assert
        $this->assertFalse($result);
    }

    public function test_delete_level_faild_unauthorized()
    {
        // Arrange
        $level = $this->level_partial;
        $competition = $this->competition_partial;
        
        $level->shouldReceive('getAttribute')->with('competition')->andReturn($competition);
        $level->shouldReceive('canEdit')->andReturn(false);
        
        $this->flasher
            ->shouldReceive('error')
            ->with(__('messages.validation.not_allow.competition_update'))
            ->once();
            
        // Act
        $result = $this->levelService->delete($level);
        
        // Assert
        $this->assertFalse($result);
    }

    public function test_activate_level_success()
    {
        // Arrange
        $newStartDate = now();
        Carbon::setTestNow($newStartDate);

        $level = $this->level_partial;
        $competition = $this->competition_partial;
        $competition->shouldReceive('getAttribute')->with('status')->andReturn('active');

        $data = $this->createLevelData(
            ['start_date' => now()->subMinutes(5)],
            strDate: false
        );
        unset($data['admin_id']);
        foreach($data as $key => $value){
            $level->shouldReceive('getAttribute')->with($key)->andReturn($value);
        }

        $level->shouldReceive('getAttribute')->with('competition')->andReturn($competition);
        $level->shouldReceive('getAttribute')->with('competition_id')->andReturn($competition->id);
        
        $questions = Mockery::mock(Collection::class);
        $questions->shouldReceive('count')->andReturn(10);
        $level->shouldReceive('getAttribute')->with('questions')->andReturn($questions);

        $level->shouldReceive('canEdit')->andReturn(true);
        $level->shouldReceive('isTheEarliest')->andReturn(true);
        $level->shouldReceive('isThePreviousAudit')->andReturn(true);
        
        $competition->shouldReceive('getAttribute')->with('status')->andReturn('1');
        $competition->shouldReceive('isAllLevelAfterNow')->andReturn(true);
        
        $this->levelRepository
            ->shouldReceive('hasTimeConflict')
            ->with($level->competition_id, $newStartDate->format('Y-m-d H:i'), 60, $level->id)
            ->once()
            ->andReturn(false);
        
            
        $this->levelRepository
            ->shouldReceive('update')
            ->with($level, ['start_date' => $newStartDate, 'status' => 'active'])
            ->once()
            ->andReturn(true);
            
        $this->userNotifyEmail
            ->shouldReceive('usersActivateLevel')
            ->with($competition, $level)
            ->once();
            
        $this->notificationService
            ->shouldReceive('levelActivated')
            ->with($competition, $level)
            ->once();
            
        $this->flasher
            ->shouldReceive('crudSuccess')
            ->with('activated')
            ->once();
            
        // Act
        $result = $this->levelService->activateLevel($level);
        
        // Assert
        $this->assertTrue($result);
    }

    public function test_activate_level_faild_already_active()
    {
        // Arrange
        $level = $this->level_partial;
        $competition = $this->competition_partial;
        
        $level->shouldReceive('getAttribute')->with('competition')->andReturn($competition);
        $level->shouldReceive('getAttribute')->with('status')->andReturn('active');
        
            
        // Act
        $result = $this->levelService->activateLevel($level);
        
        // Assert
        $this->assertFalse($result);
    }

    public function test_activate_level_faild_not_active_competition()
    {
        // Arrange
        $level = $this->level_partial;
        $competition = $this->competition_partial;
        
        $level->shouldReceive('getAttribute')->with('competition')->andReturn($competition);
        $level->shouldReceive('getAttribute')->with('status')->andReturn('pending');
        $level->shouldReceive('canEdit')->andReturn(true);
        
        $competition->shouldReceive('getAttribute')->with('status')->andReturn('pending');
        
        $this->flasher
            ->shouldReceive('error')
            ->with(__('messages.validation.not_allow.level_activate_before_competition'))
            ->once();
            
        // Act
        $result = $this->levelService->activateLevel($level);
        
        // Assert
        $this->assertFalse($result);
    }

    public function test_activate_level_faild_unauthorized()
    {
        // Arrange
        $level = $this->level_partial;
        $competition = $this->competition_partial;

        $level->shouldReceive('getAttribute')->with('competition')->andReturn($competition);
        $level->shouldReceive('getAttribute')->with('status')->andReturn('pending');
        $level->shouldReceive('canEdit')->andReturn(false);

        $this->flasher
            ->shouldReceive('error')
            ->with(__('messages.validation.not_allow.competition_update'))
            ->once();

        // Act
        $result = $this->levelService->activateLevel($level);
        
        // Assert
        $this->assertFalse($result);
    }

    public function test_activate_level_faild_start_date_greater_than_now()
    {
        // Arrange
        $level = $this->level_partial;
        $competition = $this->competition_partial;

        $level->shouldReceive('getAttribute')->with('competition')->andReturn($competition);
        $level->shouldReceive('getAttribute')->with('status')->andReturn('pending');
        $level->shouldReceive('getAttribute')->with('start_date')->andReturn(now()->addDay());
        $level->shouldReceive('canEdit')->andReturn(true);
        
        $competition->shouldReceive('getAttribute')->with('status')->andReturn('active');

        $this->flasher
            ->shouldReceive('error')
            ->with(__('messages.validation.not_allow.level_activate_early'))
            ->once();

        // Act
        $result = $this->levelService->activateLevel($level);

        // Assert
        $this->assertFalse($result);
    }

    public function test_activate_level_faild_questions_number_not_match_questions_count()
    {
        // Arrange
        $level = $this->level_partial;
        $competition = $this->competition_partial;
        $competition->shouldReceive('getAttribute')->with('status')->andReturn('active');


        $level->shouldReceive('getAttribute')->with('competition')->andReturn($competition);
        $level->shouldReceive('getAttribute')->with('status')->andReturn('pending');
        $level->shouldReceive('getAttribute')->with('start_date')->andReturn(now()->subMinutes(5));
        $level->shouldReceive('canEdit')->andReturn(true);
        
        $questions = Mockery::mock(Collection::class);
        $questions->shouldReceive('count')->andReturn(5);
        $level->shouldReceive('getAttribute')->with('questions')->andReturn($questions);

        $competition->shouldReceive('getAttribute')->with('status')->andReturn('1');

        $this->flasher
            ->shouldReceive('error')
            ->with(__('messages.validation.not_allow.level_activate_match_questions'))
            ->once();

        // Act
        $result = $this->levelService->activateLevel($level);

        // Assert
        $this->assertFalse($result);
    }

    public function test_activate_level_faild_not_its_tour()
    {
        // Arrange
        $level = $this->level_partial;
        $competition = $this->competition_partial;
        $competition->shouldReceive('getAttribute')->with('status')->andReturn('active');

        $level->shouldReceive('getAttribute')->with('competition')->andReturn($competition);
        $level->shouldReceive('getAttribute')->with('status')->andReturn('pending');
        $level->shouldReceive('getAttribute')->with('start_date')->andReturn(now()->subMinutes(5));
        $level->shouldReceive('getAttribute')->with('questions_number')->andReturn(10);
        $level->shouldReceive('canEdit')->andReturn(true);
        $level->shouldReceive('isTheEarliest')->andReturn(false);

        $questions = Mockery::mock(Collection::class);
        $questions->shouldReceive('count')->andReturn(10);
        $level->shouldReceive('getAttribute')->with('questions')->andReturn($questions);
        
        $competition->shouldReceive('getAttribute')->with('status')->andReturn('1');

        $this->flasher
            ->shouldReceive('error')
            ->with(__('messages.validation.not_allow.level_activate_not_its_tour'))
            ->once();   

        // Act
        $result = $this->levelService->activateLevel($level);

        // Assert
        $this->assertFalse($result);
    }

    public function test_activate_level_faild_previous_not_audit()
    {
        // Arrange
        $level = $this->level_partial;
        $competition = $this->competition_partial;
        $competition->shouldReceive('getAttribute')->with('status')->andReturn('active');

        $level->shouldReceive('getAttribute')->with('competition')->andReturn($competition);
        $level->shouldReceive('getAttribute')->with('status')->andReturn('pending');
        $level->shouldReceive('getAttribute')->with('start_date')->andReturn(now()->subMinutes(5));
        $level->shouldReceive('getAttribute')->with('questions_number')->andReturn(10);
        $level->shouldReceive('canEdit')->andReturn(true);
        $level->shouldReceive('isTheEarliest')->andReturn(true);
        $level->shouldReceive('isThePreviousAudit')->andReturn(false);

        $questions = Mockery::mock(Collection::class);
        $questions->shouldReceive('count')->andReturn(10);
        $level->shouldReceive('getAttribute')->with('questions')->andReturn($questions);
        
        $competition->shouldReceive('getAttribute')->with('status')->andReturn('1');

        $this->flasher
            ->shouldReceive('error')
            ->with(__('messages.validation.not_allow.level_activate_previous_not_audit'))
            ->once();   

        // Act
        $result = $this->levelService->activateLevel($level);

        // Assert
        $this->assertFalse($result);
    }

    /**
    * TODO: the activtion fail due to another level in the same competition 
    * will be earliar then the target level after update the start date
    */
    public function test_activate_level_faild_there_is_levels_with_start_date_earliest_then_now()
    {
        // Arrange
        $level = $this->level_partial;
        $competition = $this->competition_partial;
        $competition->shouldReceive('getAttribute')->with('status')->andReturn('active');

        $level->shouldReceive('getAttribute')->with('competition')->andReturn($competition);
        $level->shouldReceive('getAttribute')->with('status')->andReturn('pending');
        $level->shouldReceive('getAttribute')->with('start_date')->andReturn(now()->subMinutes(5));
        $level->shouldReceive('getAttribute')->with('questions_number')->andReturn(10);
        $level->shouldReceive('canEdit')->andReturn(true);
        $level->shouldReceive('isTheEarliest')->andReturn(true);
        $level->shouldReceive('isThePreviousAudit')->andReturn(true);

        $questions = Mockery::mock(Collection::class);
        $questions->shouldReceive('count')->andReturn(10);
        $level->shouldReceive('getAttribute')->with('questions')->andReturn($questions);
        
        $competition->shouldReceive('isAllLevelAfterNow')->with($level->id)->andReturn(false);

        $this->flasher
            ->shouldReceive('error')
            ->with(__('messages.validation.not_allow.competition_activate_level_pass'))
            ->once();   

        // Act
        $result = $this->levelService->activateLevel($level);

        // Assert
        $this->assertFalse($result);
    }

    public function test_activate_level_faild_time_conflict()
    {
        // Arrange
        Carbon::setTestNow(now());
        $level = $this->level_partial;
        $competition = $this->competition_partial;
        $competition->shouldReceive('getAttribute')->with('status')->andReturn('active');

        $level->shouldReceive('getAttribute')->with('competition')->andReturn($competition);
        $level->shouldReceive('getAttribute')->with('status')->andReturn('pending');
        $level->shouldReceive('getAttribute')->with('start_date')->andReturn(now()->subMinutes(5));
        $level->shouldReceive('getAttribute')->with('duration')->andReturn(60); 
        $level->shouldReceive('getAttribute')->with('competition_id')->andReturn(1);
        $level->shouldReceive('getAttribute')->with('questions_number')->andReturn(10);
        
        $level->shouldReceive('canEdit')->andReturn(true);
        $level->shouldReceive('isTheEarliest')->andReturn(true);
        $level->shouldReceive('isThePreviousAudit')->andReturn(true);

        $questions = Mockery::mock(Collection::class);
        $questions->shouldReceive('count')->andReturn(10);
        $level->shouldReceive('getAttribute')->with('questions')->andReturn($questions);

        $competition->shouldReceive('isAllLevelAfterNow')->andReturn(true);

        $this->levelRepository
            ->shouldReceive('hasTimeConflict')
            ->with($level->competition_id, now()->format('Y-m-d H:i'), $level->duration, $level->id)
            ->once()
            ->andReturn(true);

        $this->flasher
            ->shouldReceive('error')
            ->with(__('messages.validation.not_allow.level_activate_time_conflict'))
            ->once();

        // Act
        $result = $this->levelService->activateLevel($level);

        // Assert
        $this->assertFalse($result);
    }


    public function test_finish_level_success()
    {
        Bus::fake();
        // Arrange
        $level = $this->level_partial;
        $competition = $this->competition_partial;
        
        $level->shouldReceive('getAttribute')->with('competition')->andReturn($competition);
        $level->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $level->shouldReceive('getAttribute')->with('status')->andReturn('active');
        $level->shouldReceive('canEdit')->andReturn(true);
        $level->shouldReceive('isStillActive')->andReturn(false);
        $level->shouldReceive('fresh')->with('competition.users', 'competition.auditors')->andReturn($level);

        $competition->shouldReceive('canEdit')->andReturn(true);

        $this->notificationService
            ->shouldReceive('levelFinished')
            ->with($competition, $level)
            ->once();

        // Act
        $result = $this->levelService->finishLevel($level);
        
        // Assert
        $this->assertTrue($result);
        Bus::assertDispatched(FinishLevelJob::class, function ($job) use ($level) {
            return $job->getLevel()->id === $level->id;
        });
    }

    public function test_finish_level_faild_unauthorized()
    {
        Bus::fake();
        // Arrange
        $level = $this->level_partial;
        $competition = $this->competition_partial;
        
        $level->shouldReceive('getAttribute')->with('competition')->andReturn($competition);
        $level->shouldReceive('canEdit')->andReturn(false);

        $this->flasher
            ->shouldReceive('error')
            ->with(__('messages.validation.not_allow.level_edit_restricted_finish'))
            ->once();

        // Act
        $result = $this->levelService->finishLevel($level);
        
        // Assert
        $this->assertFalse($result);
        Bus::assertNotDispatched(FinishLevelJob::class);
    }

    public function test_finish_level_faild_still_active()
    {
        Bus::fake();
        // Arrange
        $level = $this->level_partial;
        $competition = $this->competition_partial;
        
        $level->shouldReceive('getAttribute')->with('competition')->andReturn($competition);
        $level->shouldReceive('canEdit')->andReturn(true);
        $level->shouldReceive('isStillActive')->andReturn(true);

        $this->flasher
            ->shouldReceive('error')
            ->with(__('messages.validation.not_allow.level_finish_still_active'))
            ->once();

        // Act
        $result = $this->levelService->finishLevel($level);
        
        // Assert
        $this->assertFalse($result);
        Bus::assertNotDispatched(FinishLevelJob::class);
    }
    
} 