<?php

namespace Tests\Unit\Services\Competition;

use Bus;
use Mockery;
use Tests\TestCase;
use App\Models\User;
use App\Models\Admin\Admin;
use App\Helpers\UserNotifyEmail;
use App\Models\Competition\Level;
use Illuminate\Support\Facades\DB;
use App\Contracts\FlasherInterface;
use Illuminate\Support\Facades\Auth;
use App\Services\SystemSettingService;
use App\Models\Competition\Competition;
use App\Services\Admin\AdminApprovalService;
use App\Contracts\TransactionManagerInterface;
use App\Services\Monitoring\JobTrackingService;
use App\Services\Competition\CompetitionService;
use App\Services\Payment\CoinTransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Jobs\Competetion\SyncCompetitionParticipants;
use App\Exceptions\AIQuestionGeneration\PaidServiceException;
use App\Interface\Competition\CompetitionRepositoryInterface;
use App\Services\Notification\OptimizedCompetitionNotificationService;

class CompetitionServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @var CompetitionService */
    protected $service;
    protected $competitionRepository;
    protected $transactionManager;
    protected $flasher;
    protected $jobTrackingService;
    protected $notificationService;
    protected $approvalService;
    protected $coinTransactionService;
    protected $systemSettingService;
    protected $mainAdmin;
    protected $userNotify;

    protected function setUp(): void
    {
        parent::setUp();
        $this->competitionRepository = Mockery::mock(CompetitionRepositoryInterface::class);
        $this->transactionManager = Mockery::mock(TransactionManagerInterface::class);
        $this->flasher = Mockery::mock(FlasherInterface::class);
        $this->jobTrackingService = Mockery::mock(JobTrackingService::class);
        $this->notificationService = Mockery::mock(OptimizedCompetitionNotificationService::class);
        $this->approvalService = Mockery::mock(AdminApprovalService::class);
        $this->coinTransactionService = Mockery::mock(CoinTransactionService::class);
        $this->systemSettingService = Mockery::mock(SystemSettingService::class);
        $this->service = new CompetitionService(
            $this->competitionRepository,
            $this->transactionManager,
            $this->flasher,
            $this->jobTrackingService,
            $this->notificationService,
            $this->approvalService,
            $this->systemSettingService,
            $this->coinTransactionService
        );
        $this->userNotify = Mockery::mock('alias:'.UserNotifyEmail::class);

        $this->mainAdmin = Admin::factory()->create(['name' => 'main admin']);
        Auth::shouldReceive('id')->andReturn($this->mainAdmin->id);
        Auth::shouldReceive('user')->andReturn($this->mainAdmin);
        Bus::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_find_competition_by_id_returns_competition()
    {
        $competition = Competition::factory()->create();
        $this->competitionRepository->shouldReceive('findById')->with($competition->id)->andReturn($competition);
        $result = $this->service->findCompetitionById(base64_encode($competition->id));
        $this->assertEquals($competition->id, $result->id);
    }

    public function test_create_competition_success()
    {
        $this->mainAdmin->coinBalance->update(['balance' => 500]);
        $data = Competition::factory()
                    ->make([
                        'admin_id' => $this->mainAdmin->id,
                        'winner_gifts' => 100,
                        'multi_winner' => false,
                        'ai_auditing' => false,
                    ])->toArray();
        $competitionGift = 50;
        $this->systemSettingService
                        ->shouldReceive('getValueAsInt')
                        ->with('min_competition_coins')
                        ->andReturn($competitionGift);

        $this->transactionManager->shouldReceive('run')->andReturnUsing(fn($cb) => $cb());
        $this->flasher->shouldReceive('crudSuccess')->with('saved')->once();
        $this->notificationService->shouldReceive('competitionCreated')->once();
        $this->coinTransactionService->shouldReceive('createCompetitionWinnerGiftTransaction')->once();
        $result = $this->service->createCompetition($data);

        $this->assertTrue($result);
        $this->assertDatabaseHas('competitions', ['title' => $data['title']]);
        Bus::assertDispatched(SyncCompetitionParticipants::class);
    }

    public function test_create_competition_fail_competition_gift_less_than_min_competition_coins()
    {
        $this->mainAdmin->coinBalance->update(['balance' => 500]);
        $data = Competition::factory()
                    ->make([
                        'admin_id' => $this->mainAdmin->id,
                        'winner_gifts' => 100,
                        'multi_winner' => false,
                        'ai_auditing' => false,
                    ])->toArray();
        $competitionGift = 200;
        $this->systemSettingService
                        ->shouldReceive('getValueAsInt')
                        ->with('min_competition_coins')
                        ->andReturn($competitionGift);

        $this->flasher->shouldReceive('error')
                ->with(__('messages.validation.not_allow.competition_create_less_gift', ['gift' => $competitionGift]))
                ->once();
        $result = $this->service->createCompetition($data);

        $this->assertFalse($result);
        $this->assertDatabaseMissing('competitions', ['title' => $data['title']]);
        Bus::assertNotDispatched(SyncCompetitionParticipants::class);
    }

    public function test_create_competition_fail_insufficient_balance()
    {
        $this->mainAdmin->coinBalance->update(['balance' => 0]);
        $data = Competition::factory()
                    ->make([
                        'admin_id' => $this->mainAdmin->id,
                        'winner_gifts' => 100,
                        'multi_winner' => false,
                        'ai_auditing' => false,
                    ])->toArray();
        $competitionGift = 50;
        $this->systemSettingService
                        ->shouldReceive('getValueAsInt')
                        ->with('min_competition_coins')
                        ->andReturn($competitionGift);

        $this->flasher->shouldReceive('error')
        ->with(__('messages.validation.not_allow.service_insufficient_balance'))
        ->once();
        $result = $this->service->createCompetition($data);

        $this->assertFalse($result);
    }

    public function test_create_competition_handles_exception()
    {
        $data = Competition::factory()->make()->toArray();
        $this->transactionManager->shouldReceive('run')->andThrow(new \Exception('DB error'));
        $this->flasher->shouldReceive('crudFailure')->with('saved')->once();

        $result = $this->service->createCompetition($data);

        $this->assertFalse($result);
        Bus::assertNotDispatched(SyncCompetitionParticipants::class);
    }

    public function test_update_competition_success_with_resync()
    {
        $competition = Competition::factory()->create();
        $data = ['title' => 'Updated Title'];
        $this->transactionManager->shouldReceive('run')->andReturnUsing(fn($cb) => $cb());
        $this->flasher->shouldReceive('crudSuccess')->with('updated')->once();
        $this->notificationService->shouldReceive('competitionUpdated')->once();
        $this->competitionRepository
        ->shouldReceive('update')
        ->with($competition,$data)
        ->andReturn([
            'competition' => $competition,
            'resyncCompetitionParticipants' => true
        ]);

        $result = $this->service->updateCompetition($competition, $data);

        $this->assertTrue($result);
        Bus::assertDispatched(SyncCompetitionParticipants::class);
    }

    public function test_update_competition_success_without_resync()
    {
        $competition = Competition::factory()->create();
        $data = ['title' => 'Updated Title'];
        $this->transactionManager->shouldReceive('run')->andReturnUsing(fn($cb) => $cb());
        $this->flasher->shouldReceive('crudSuccess')->with('updated')->once();
        $this->notificationService->shouldReceive('competitionUpdated')->once();
        $this->competitionRepository
            ->shouldReceive('update')
            ->with($competition,$data)
            ->andReturn([
                'competition' => $competition,
                'resyncCompetitionParticipants' => false
            ]);
        
        $this->userNotify->shouldReceive('usersUpdateCompetition')
                        ->with($competition)
                        ->andReturnNull();


        $result = $this->service->updateCompetition($competition, $data);

        $this->assertTrue($result);
        Bus::assertNotDispatched(SyncCompetitionParticipants::class);
        
    }

    public function test_update_competition_handles_exception()
    {
        $competition = Competition::factory()->create();
        $data = ['title' => 'Updated Title'];
        $this->transactionManager->shouldReceive('run')->andThrow(new \Exception('DB error'));
        $this->flasher->shouldReceive('crudFailure')->with('updated')->once();

        $result = $this->service->updateCompetition($competition, $data);

        $this->assertFalse($result);
        Bus::assertNotDispatched(SyncCompetitionParticipants::class);
    }

    public function test_delete_competition_success()
    {
        $competition = Competition::factory()->create(['admin_id' => $this->mainAdmin->id]);
        $this->competitionRepository->shouldReceive('findById')->andReturn($competition);
        $this->flasher->shouldReceive('crudSuccess')->with('deleted')->once();

        $result = $this->service->deleteCompetition($competition->id);

        $this->assertTrue($result);
    }

    public function test_delete_competition_fail_unauthorized()
    {
        $admin = Admin::factory()->create();
        $competition = Competition::factory()->create(['admin_id' => $admin->id]);
        $this->competitionRepository->shouldReceive('findById')->andReturn($competition);
        $this->flasher->shouldReceive('error')
            ->with(__('messages.validation.not_allow.competition_delete'))
            ->once();

        $result = $this->service->deleteCompetition($competition->id);

        $this->assertFalse($result);
    }

    public function test_delete_competition_handles_not_found()
    {
        $this->competitionRepository->shouldReceive('findById')->andReturn(null);
        $this->flasher->shouldReceive('error')->once();
        $result = $this->service->deleteCompetition(999);
        $this->assertFalse($result);
    }


    public function test_add_competition_users_success()
    {
        $competition = Competition::factory()->create();
        $users = User::factory()->count(2)->create();
        $this->transactionManager->shouldReceive('run')->andReturnUsing(fn($cb) => $cb());
        $this->notificationService->shouldReceive('notifyUsers')->once();
        $this->flasher->shouldReceive('crudSuccess')->with('saved')->once();
        $this->competitionRepository->shouldReceive('addUsersToCompetition')->once();
        
        $result = $this->service->addCompetitionUsers($competition, $users->pluck('id')->toArray());
        
        $this->assertTrue($result);
    }

    public function test_add_competition_users_handles_exception()
    {
        $competition = Competition::factory()->create();
        $users = User::factory()->count(2)->create();
        $this->transactionManager->shouldReceive('run')->andThrow(new \Exception('DB error'));
        $this->flasher->shouldReceive('crudFailure')->with('saved')->once();
        
        $result = $this->service->addCompetitionUsers($competition, $users->pluck('id')->toArray());
        
        $this->assertFalse($result);
    }

    public function test_remove_competition_user_success()
    {
        $competition = Competition::factory()->create();
        $user = User::factory()->create();
        $competition->users()->attach($user->id);
        $competition = $competition->fresh();

        
        $this->transactionManager->shouldReceive('run')->andReturnUsing(fn($cb) => $cb());
        $this->flasher->shouldReceive('crudSuccess')->with('deleted')->once();
        
        $result = $this->service->removeCompetitionUser($competition, $user->id);
        
        $this->assertTrue($result);
        $this->assertDatabaseMissing('competition_user', [
            'competition_id' => $competition->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_remove_competition_user_fail_unauthorized()
    {
        $admin = Admin::factory()->create();
        $competition = Competition::factory()->create(['admin_id' => $admin->id]);
        $user = User::factory()->create();
        $competition->users()->attach($user->id);
        $competition = $competition->fresh();
        $this->flasher->shouldReceive('error')->once();

        $result = $this->service->removeCompetitionUser($competition, $user->id);

        $this->assertFalse($result);
    }

    public function test_remove_competition_user_handles_exception()
    {
        $competition = Competition::factory()->create();
        $user = User::factory()->create();
        $competition->users()->attach($user->id);
        $competition = $competition->fresh();
        $this->transactionManager->shouldReceive('run')->andThrow(new \Exception('DB error'));
        $this->flasher->shouldReceive('crudFailure')->with('deleted')->once();
        
        $result = $this->service->removeCompetitionUser($competition, $user->id);
        
        $this->assertFalse($result);
    }

    public function test_request_auditor_assignment_success()
    {
        $competition = Competition::factory()->create();
        $auditors = Admin::factory()->count(2)->create();
        $competition = $competition->fresh();
        $this->transactionManager->shouldReceive('run')->andReturnUsing(fn($cb) => $cb());

        $approvalStatus = [
            'new' => $auditors->map(function($admin){
                return ['id' => $admin->id];
            })->toArray(),
            'pending' => [['name' => 'admin1']],
            'rejected' => [['name' => 'admin2']],
            'approved' => [['name' => 'admin3']],
        ];
        $this->approvalService->shouldReceive('getApprovalStatusForMultipleAdmins')->once()->andReturn($approvalStatus);
        $this->approvalService->shouldReceive('insertBulkApprovalRequests')->once()->andReturn($auditors);
        $this->flasher->shouldReceive('info')->once()->with(__('messages.validation.success.auditor_assignment_requested', ['admins' => implode(', ', $auditors->pluck('name')->toArray())]));
        $this->flasher->shouldReceive('error')->once()->with(__('messages.validation.error.auditor_assignment_requested_pending', ['admins' => implode(', ', array_column($approvalStatus['pending'], 'name'))]));
        $this->flasher->shouldReceive('error')->once()->with(__('messages.validation.error.auditor_assignment_requested_rejected', ['admins' => implode(', ', array_column($approvalStatus['rejected'], 'name'))]));
        $this->flasher->shouldReceive('error')->once()->with(__('messages.validation.error.auditor_assignment_requested_approved', ['admins' => implode(', ', array_column($approvalStatus['approved'], 'name'))]));

        $this->notificationService->shouldReceive('auditorRequestedBulk')->once();
        
        $result = $this->service->requestAuditorAssignment($competition, $auditors->pluck('id')->toArray());
        
        $this->assertTrue($result);
    }

    public function test_request_auditor_assignment_faild_unauthorized()
    {
        $auditors = Admin::factory()->count(2)->create();
        $competition = Competition::factory()->create(['admin_id' => $auditors->first()->id]);
        $competition = $competition->fresh();
        $this->flasher->shouldReceive('error')->once();
        
        $result = $this->service->requestAuditorAssignment($competition, $auditors->pluck('id')->toArray());
        
        $this->assertFalse($result);
    }

    public function test_request_auditor_assignment_handles_exception()
    {
        $competition = Competition::factory()->create();
        $auditors = Admin::factory()->count(2)->create();
        $competition = $competition->fresh();
        $this->transactionManager->shouldReceive('run')->andThrow(new \Exception('DB error'));
        $this->flasher->shouldReceive('crudFailure')->with('saved')->once();
        
        $result = $this->service->requestAuditorAssignment($competition, $auditors->pluck('id')->toArray());
        
        $this->assertFalse($result);
    }

    public function test_remove_competition_auditor_success()
    {
        $competition = Competition::factory()->create();
        $auditors = Admin::factory()->count(2)->create();
        $competition->auditors()->attach($auditors->pluck('id'));
        $competition = $competition->fresh();

        $this->jobTrackingService->shouldReceive('dispatchWithTracking')->once();
        $this->flasher->shouldReceive('info')->once();

        $result = $this->service->removeCompetitionAuditor($competition, $auditors[0]->id);
        
        $this->assertTrue($result);
        
    }

    public function test_remove_competition_auditor_faild_unauthorized()
    {
        $auditors = Admin::factory()->count(2)->create();
        $competition = Competition::factory()->create(['admin_id' => $auditors->first()->id]);
        $competition->auditors()->attach($auditors->pluck('id'));
        $competition = $competition->fresh();
        $this->flasher->shouldReceive('error')->once();
        
        $result = $this->service->removeCompetitionAuditor($competition, $auditors[0]->id);
        
        $this->assertFalse($result);
    }

    public function test_remove_competition_faild_auditor_only_one_auditor()
    {
        $competition = Competition::factory()->create();
        $auditor = Admin::factory()->create();
        $competition->auditors()->attach($auditor->id);
        $competition = $competition->fresh();
        $this->flasher->shouldReceive('error')->once();
        
        $result = $this->service->removeCompetitionAuditor($competition, $auditor->id);
        
        $this->assertFalse($result);
    }

    public function test_activate_competition_success()
    {
        $competition = Competition::factory()->create([
            'start_date' => now()->subDay(),
            'levels_number' => 2,
            'status' => 'pending',
        ]);
        $competition->levels()->saveMany(Level::factory()->count(2)->make());
        $competition->users()->attach(User::factory()->count(3)->create()->pluck('id'));
        $competition->auditors()->attach(Admin::factory()->count(2)->create()->pluck('id'));
        $competition = $competition->fresh();
        /** @var Competition $competition */
        $competition = \Mockery::mock($competition)->makePartial();
        $competition->shouldReceive('isAllLevelAfterNow')->andReturn(true);
        $this->transactionManager->shouldReceive('run')->andReturnUsing(fn($cb) => $cb());
        $this->notificationService->shouldReceive('competitionActivated')->once();
        $this->flasher->shouldReceive('crudSuccess')->with('activated')->once();
        $this->userNotify->shouldReceive('usersActivateCompetition')->once();
        
        $result = $this->service->activateCompetition($competition);
        $this->assertTrue($result);
        
        $this->assertEquals('active', $competition->status);
    }

    public function test_activate_competition_start_date_too_early()
    {
        $competition = Competition::factory()->create([
            'start_date' => now()->addDay(),
            'levels_number' => 2,
            'status' => 'pending',
        ]);
        /** @var Competition $competition */
        $competition = \Mockery::mock($competition)->makePartial();
        $competition->shouldReceive('start_date')->andReturn(now()->addDay());
        $this->flasher->shouldReceive('error')->once();
        
        $result = $this->service->activateCompetition($competition);
        
        $this->assertFalse($result);
    }

    public function test_activate_competition_levels_mismatch()
    {
        $competition = Competition::factory()->create([
            'start_date' => now()->subDay(),
            'levels_number' => 2,
            'status' => 'pending',
        ]);
        $competition->levels()->saveMany(Level::factory()->count(1)->make());
        $competition->users()->attach(User::factory()->count(3)->create()->pluck('id'));
        $competition->auditors()->attach(Admin::factory()->count(2)->create()->pluck('id'));
        $competition = $competition->fresh();
        $this->flasher->shouldReceive('error')->once();
        
        $result = $this->service->activateCompetition($competition);
        
        $this->assertFalse($result);
    }

    public function test_activate_competition_not_enough_users()
    {
        $competition = Competition::factory()->create([
            'start_date' => now()->subDay(),
            'levels_number' => 2,
            'status' => 'pending',
        ]);
        $competition->levels()->saveMany(Level::factory()->count(2)->make());
        $competition->users()->attach(User::factory()->count(2)->create()->pluck('id'));
        $competition->auditors()->attach(Admin::factory()->count(2)->create()->pluck('id'));
        $competition = $competition->fresh();
        $this->flasher->shouldReceive('error')->once();
        
        $result = $this->service->activateCompetition($competition);
        
        $this->assertFalse($result);
    }

    public function test_activate_competition_no_auditors()
    {
        $competition = Competition::factory()->create([
            'start_date' => now()->subDay(),
            'levels_number' => 2,
            'status' => 'pending',
        ]);
        $competition->levels()->saveMany(Level::factory()->count(2)->make());
        $competition->users()->attach(User::factory()->count(3)->create()->pluck('id'));
        $competition = $competition->fresh();
        $this->flasher->shouldReceive('error')->once();
        
        $result = $this->service->activateCompetition($competition);
        
        $this->assertFalse($result);
    }

    public function test_activate_competition_levels_in_past()
    {
        $competition = Competition::factory()->create([
            'start_date' => now()->subDay(),
            'levels_number' => 2,
            'status' => 'pending',
        ]);
        $competition->levels()->saveMany(Level::factory()->count(2)->make());
        $competition->users()->attach(User::factory()->count(3)->create()->pluck('id'));
        $competition->auditors()->attach(Admin::factory()->count(2)->create()->pluck('id'));
        $competition = $competition->fresh();
        /** @var Competition $competition */
        $competition = \Mockery::mock($competition)->makePartial();
        $competition->shouldReceive('isAllLevelAfterNow')->andReturn(false);
        $this->flasher->shouldReceive('error')->once();
        
        $result = $this->service->activateCompetition($competition);
        
        $this->assertFalse($result);
    }

    public function test_activate_competition_handles_exception()
    {
        $competition = Competition::factory()->create([
            'start_date' => now()->subDay(),
            'levels_number' => 2,
            'status' => 'pending',
        ]);
        $competition->levels()->saveMany(Level::factory()->count(2)->make());
        $competition->users()->attach(User::factory()->count(3)->create()->pluck('id'));
        $competition->auditors()->attach(Admin::factory()->count(2)->create()->pluck('id'));
        $competition = $competition->fresh();
        /** @var Competition $competition */
        $competition = \Mockery::mock($competition)->makePartial();
        $competition->shouldReceive('isAllLevelAfterNow')->andReturn(true);
        $this->transactionManager->shouldReceive('run')->andThrow(new \Exception('DB error'));
        $this->flasher->shouldReceive('crudFailure')->with('activated')->once();
        
        $result = $this->service->activateCompetition($competition);
        
        $this->assertFalse($result);
    }

    public function test_create_delete_job_creates_job_with_correct_arguments()
    {
        $admin = Admin::factory()->make(['id' => 123]);
        $competition = Competition::factory()->make(['id' => 456]);

        $method = (new \ReflectionClass($this->service))->getMethod('createDeleteJob');
        $method->setAccessible(true);
        $result = $method->invoke($this->service, $admin, $competition);
        $this->assertInstanceOf(\App\Jobs\Competition\SafeDeleteAuditorJob::class, $result);
        $this->assertEquals($admin->id, $result->getAuditor()->id);
        $this->assertEquals($competition->id, $result->getCompetition()->id);
        $this->assertEquals(1, $result->getUserId());
    }
}
