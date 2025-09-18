<?php

namespace App\Providers;

use Carbon\Carbon;
use App\Models\Admin\Admin;
use App\Services\User\UserService;
use App\Contracts\FlasherInterface;
use App\Services\Admin\AdminService;
use App\Services\Notification\Flasher;
use App\Services\SystemSettingService;
use App\Services\LLM\LLMHandlerFactory;
use Illuminate\Support\ServiceProvider;
use App\Services\Payment\PaymentService;
use Illuminate\Validation\Rules\Password;
use App\Services\Competition\AuditService;
use App\Services\Competition\LevelService;
use App\Services\Admin\AdminProfileService;
use App\Services\Admin\AdminApprovalService;
use App\Services\Payment\CoinPricingService;
use App\Services\Competition\QuestionService;
use App\Services\Database\TransactionManager;
use App\Services\GuestUsers\UserGuestService;
use App\Services\User\UserCompetitionService;
use App\Contracts\TransactionManagerInterface;
use App\Services\Payment\PaymentReviewService;
use App\Repository\Competition\AuditRepository;
use App\Repository\Competition\LevelRepository;
use App\Services\Monitoring\JobTrackingService;
use App\Repository\Admin\AdminProfileRepository;
use App\Services\Competition\CompetitionService;
use App\Services\Monitoring\DuplicateJobChecker;
use App\Services\Payment\CoinTransactionService;
use Illuminate\Auth\Notifications\ResetPassword;
use App\Repository\GuestUsers\UserGuestRepository;
use App\Repository\User\UserCompetitionRepository;
use App\Services\GuestUsers\GlobalQuestionService;
use App\Factories\Monitoring\RestoreHandlerFactory;
use App\Jobs\Competition\AIAuditingBatchJobFactory;
use App\Services\Monitoring\DeletionRecordsService;
use App\Repository\Competition\CompetitionRepository;
use App\Factories\Monitoring\HardDeleteHandlerFactory;
use App\Services\CashManagment\PaymentCacheManagement;
use App\Interface\Competition\AuditRepositoryInterface;
use App\Interface\Competition\LevelRepositoryInterface;
use App\Interface\Admin\AdminProfileRepositoryInterface;
use App\Jobs\Competition\FinishLevelTrackableJobFactory;
use App\Services\Notification\PaymentNotificationService;
use App\Services\ProcessManagement\DelayedProcessService;
use App\Interface\GuestUsers\UserGuestRepositoryInterface;
use App\Interface\Monitoring\JobTrackingStrategyInterface;
use App\Interface\User\UserCompetitionRepositoryInterface;
use App\Repository\Monitoring\DatabaseJobTrackingStrategy;
use App\Repository\Monitoring\InMemoryJobTrackingStrategy;
use App\Interface\Competition\CompetitionRepositoryInterface;
use App\Services\CashManagment\CompetitionCacheManagmentSystem;
use App\Services\Notification\OptimizedCompetitionNotificationService;
use App\View\Composers\SidebarComposer;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AdminProfileRepositoryInterface::class, AdminProfileRepository::class);
        $this->app->bind(AdminProfileService::class, function ($app) {
            return new AdminProfileService($app->make(AdminProfileRepositoryInterface::class));
        });
        $this->app->bind(AdminService::class, function ($app) {
            return new AdminService(
                $app->make(TransactionManagerInterface::class),
                $app->make(FlasherInterface::class),
                $app->make(JobTrackingService::class)
            );
        });

        // user
        $this->app->bind(UserService::class, function ($app) {
            return new UserService(
                $app->make(FlasherInterface::class),
                $app->make(JobTrackingService::class)
            );
        });

        // competition
        $this->app->bind(CompetitionRepositoryInterface::class, CompetitionRepository::class);
        $this->app->bind(CompetitionService::class, function ($app) {
            return new CompetitionService(
                $app->make(CompetitionRepositoryInterface::class),
                $app->make(TransactionManagerInterface::class),
                $app->make(FlasherInterface::class),
                $app->make(JobTrackingService::class),
                $app->make(OptimizedCompetitionNotificationService::class),
                $app->make(AdminApprovalService::class),
                $app->make(SystemSettingService::class),
                $app->make(CoinTransactionService::class),
                $app->make(CompetitionCacheManagmentSystem::class)
            );
        });

        // level
        $this->app->bind(LevelRepositoryInterface::class, LevelRepository::class);
        $this->app->bind(LevelService::class, function ($app) {
            return new LevelService(
                $app->make(LevelRepositoryInterface::class),
                $app->make(TransactionManagerInterface::class),
                $app->make(FlasherInterface::class),
                $app->make(OptimizedCompetitionNotificationService::class),
                $app->make(AdminApprovalService::class),
                $app->make(JobTrackingService::class),
                $app->make(FinishLevelTrackableJobFactory::class),
                $app->make(SystemSettingService::class),
                $app->make(CompetitionCacheManagmentSystem::class),
            );
        });

        // question
        $this->app->bind(QuestionService::class, function ($app) {
            return new QuestionService(
                $app->make(TransactionManagerInterface::class),
                $app->make(FlasherInterface::class)
            );
        });

        // user competition

        // approval services
        $this->app->bind(\App\Contracts\Approval\ApprovalFactoryInterface::class, \App\Factories\Approval\ApprovalHandlerFactory::class);
        $this->app->bind(UserCompetitionRepositoryInterface::class, UserCompetitionRepository::class);
        $this->app->bind(UserCompetitionService::class, function ($app) {
            return new UserCompetitionService(
                $app->make(UserCompetitionRepositoryInterface::class),
                $app->make(TransactionManagerInterface::class),
                $app->make(FlasherInterface::class),
                $app->make(CompetitionCacheManagmentSystem::class)
            );
        });

        // auditing
        $this->app->bind(AuditRepositoryInterface::class, AuditRepository::class);
        $this->app->bind(AuditService::class, function ($app) {
            return new AuditService(
                $app->make(AuditRepositoryInterface::class),
                $app->make(TransactionManagerInterface::class),
                $app->make(FlasherInterface::class),
                $app->make(CompetitionCacheManagmentSystem::class)
            );
        });

        // global questions
        $this->app->bind(GlobalQuestionService::class, function ($app) {
            return new GlobalQuestionService(
                $app->make(FlasherInterface::class),
                $app->make(TransactionManagerInterface::class)
            );
        });

        // user global questions
        $this->app->bind(UserGuestRepositoryInterface::class, UserGuestRepository::class);
        $this->app->bind(UserGuestService::class, function ($app) {
            return new UserGuestService(
                $app->make(UserGuestRepositoryInterface::class),
                $app->make(FlasherInterface::class),
                $app->make(TransactionManagerInterface::class),
                $app->make(SystemSettingService::class),
                $app->make(CoinTransactionService::class)
            );
        });

        // transaction manager
        $this->app->bind(TransactionManagerInterface::class, TransactionManager::class);

        // flasher
        $this->app->bind(FlasherInterface::class, Flasher::class);

        // flasher helper
        $this->app->singleton('flasher', function ($app) {
            return $app->make(FlasherInterface::class);
        });

        // job tracking strategy
        $this->app->bind(JobTrackingStrategyInterface::class, function ($app) {
            // ✅ FOLLOWS: Environment-based configuration done in service provider, not business logic
            if ($app->environment('testing')) {
                return new InMemoryJobTrackingStrategy();
            }
            return new DatabaseJobTrackingStrategy();
        });

        // system settings service (singleton for performance)
        $this->app->singleton(SystemSettingService::class, function ($app) {
            return new SystemSettingService();
        });

        // coin pricing service (singleton for performance - expensive DB queries, no user state)
        $this->app->singleton(CoinPricingService::class, function ($app) {
            return new CoinPricingService(
                $app->make(TransactionManagerInterface::class),
                $app->make(FlasherInterface::class)
            );
        });

        // payment service
        $this->app->bind(PaymentService::class, function ($app) {
            return new PaymentService(
                $app->make(TransactionManagerInterface::class),
                $app->make(FlasherInterface::class),
                $app->make(CoinPricingService::class),
                $app->make(PaymentNotificationService::class),
                $app->make(CoinTransactionService::class)
            );
        });

        // payment review service (singleton for performance - heavy dependencies, no user state)
        $this->app->singleton(PaymentReviewService::class, function ($app) {
            return new PaymentReviewService(
                $app->make(TransactionManagerInterface::class),
                $app->make(FlasherInterface::class),
                $app->make(PaymentService::class),
                $app->make(PaymentNotificationService::class)
            );
        });

        // optimized competition notification service (singleton for performance - batch operations, no user state)
        $this->app->singleton(OptimizedCompetitionNotificationService::class, function ($app) {
            return new OptimizedCompetitionNotificationService();
        });

        // payment cache management (after PaymentService is registered)
        $this->app->bind(PaymentCacheManagement::class, function ($app) {
            return new PaymentCacheManagement(
                $app->make(PaymentService::class),
                $app->make(CoinTransactionService::class)
            );
        });

        // delayed process service (bind - lightweight, no dependencies)
        $this->app->bind(DelayedProcessService::class);

        // duplicate job checker (bind - used by JobTrackingService)
        $this->app->bind(DuplicateJobChecker::class);

        // deletion records service (bind - has dependencies)
        $this->app->bind(DeletionRecordsService::class, function ($app) {
            return new DeletionRecordsService(
                $app->make(TransactionManagerInterface::class),
                $app->make(JobTrackingService::class),
                $app->make(FlasherInterface::class),
                $app->make(RestoreHandlerFactory::class),
                $app->make(HardDeleteHandlerFactory::class)
            );
        });

        // approval assignment service (bind - system integration)
        $this->app->bind(\App\Services\Approval\ApprovalAssignmentService::class);

        // In AppServiceProvider.php boot() method or register() method
        $this->app->bind(FinishLevelTrackableJobFactory::class, function ($app) {
            return new FinishLevelTrackableJobFactory(
                $app->make(LevelRepositoryInterface::class),
                $app->make(OptimizedCompetitionNotificationService::class),
                $app->make(JobTrackingService::class)
            );
        });

        // AI Auditing Batch Job Factory
        $this->app->bind(AIAuditingBatchJobFactory::class, function ($app) {
            return new AIAuditingBatchJobFactory(
                $app->make(LLMHandlerFactory::class),
                $app->make(SystemSettingService::class),
                $app->make(JobTrackingService::class),
                $app->make(AuditService::class),
                $app->make(OptimizedCompetitionNotificationService::class),
                $app->make(CompetitionCacheManagmentSystem::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Implicitly grant "Super-Admin" role all permission checks using can()

        Password::defaults(function () {
            return Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols();
        });

        ResetPassword::createUrlUsing(function ($user, string $token) {
            if ($user instanceof Admin)
                return url(route('admin.password.reset', [
                    'token' => $token,
                    'email' => $user->email,
                ], false));
            else
                return url(route('password.reset', [
                    'token' => $token,
                    'email' => $user->email,
                ], false));

        });

        Carbon::macro('inUserTimezone', function() {
            /** @var \Carbon\Carbon $this */
            return $this->setTimezone(session()->get('timezone') ?? config('app.timezone_display'));
        });

    }
}
