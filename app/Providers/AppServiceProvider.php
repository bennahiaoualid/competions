<?php

namespace App\Providers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Admin\Admin;
use App\Services\User\UserService;
use App\Contracts\FlasherInterface;
use App\Providers\AllUsersProvider;
use App\Services\Admin\AdminService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Event;
use App\Services\Notification\Flasher;
use App\Repository\User\UserRepository;
use Illuminate\Support\ServiceProvider;
use App\Repository\Admin\AdminRepository;
use Illuminate\Validation\Rules\Password;
use App\Services\Competition\AuditService;
use App\Services\Competition\LevelService;
use App\Services\Admin\AdminProfileService;
use App\Services\Competition\QuestionService;
use App\Services\Database\TransactionManager;
use App\Services\GuestUsers\UserGuestService;
use App\Services\User\UserCompetitionService;
use App\Contracts\TransactionManagerInterface;
use App\Interface\User\UserRepositoryInterface;
use App\Repository\Competition\AuditRepository;
use App\Repository\Competition\LevelRepository;
use App\Services\Monitoring\JobTrackingService;
use App\Repository\Admin\AdminProfileRepository;
use App\Services\Competition\CompetitionService;
use Illuminate\Auth\Notifications\ResetPassword;
use App\Events\Monitoring\JobRetriedSuccessfully;
use App\Interface\Admin\AdminRepositoryInterface;
use App\Repository\Competition\QuestionRepository;
use App\Repository\GuestUsers\UserGuestRepository;
use App\Repository\User\UserCompetitionRepository;
use App\Services\GuestUsers\GlobalQuestionService;
use App\Listeners\HandleAdminDeletionAfterJobSuccess;
use App\Repository\Competition\CompetitionRepository;
use App\Interface\Competition\AuditRepositoryInterface;
use App\Interface\Competition\LevelRepositoryInterface;
use App\Repository\GuestUsers\GlobalQuestionRepository;
use App\Interface\Admin\AdminProfileRepositoryInterface;
use App\Interface\Competition\QuestionRepositoryInterface;
use App\Interface\GuestUsers\UserGuestRepositoryInterface;
use App\Interface\Monitoring\JobTrackingStrategyInterface;
use App\Interface\User\UserCompetitionRepositoryInterface;
use App\Repository\Monitoring\InMemoryJobTrackingStrategy;
use App\Repository\Monitoring\DatabaseJobTrackingStrategy;
use App\Interface\Competition\CompetitionRepositoryInterface;
use App\Interface\GuestUsers\GlobalQuestionRepositoryInterface;

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
        $this->app->bind(AdminRepositoryInterface::class, AdminRepository::class);
        $this->app->bind(AdminService::class, function ($app) {
            return new AdminService(
                $app->make(AdminRepositoryInterface::class),
                $app->make(TransactionManagerInterface::class),
                $app->make(FlasherInterface::class),
                $app->make(JobTrackingService::class)
            );
        });

        // user
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(UserService::class, function ($app) {
            return new UserService($app->make(UserRepositoryInterface::class));
        });

        // competition
        $this->app->bind(CompetitionRepositoryInterface::class, CompetitionRepository::class);
        $this->app->bind(CompetitionService::class, function ($app) {
            return new CompetitionService(
                $app->make(CompetitionRepositoryInterface::class),
                $app->make(TransactionManagerInterface::class),
                $app->make(FlasherInterface::class),
                $app->make(JobTrackingService::class)
            );
        });

        // level
        $this->app->bind(LevelRepositoryInterface::class, LevelRepository::class);
        $this->app->bind(LevelService::class, function ($app) {
            return new LevelService(
                $app->make(LevelRepositoryInterface::class),
                $app->make(TransactionManagerInterface::class),
                $app->make(FlasherInterface::class)
            );
        });

        // question
        $this->app->bind(QuestionRepositoryInterface::class, QuestionRepository::class);
        $this->app->bind(QuestionService::class, function ($app) {
            return new QuestionService(
                $app->make(QuestionRepositoryInterface::class),
                $app->make(TransactionManagerInterface::class),
                $app->make(FlasherInterface::class)
            );
        });

        // user competition
        $this->app->bind(UserCompetitionRepositoryInterface::class, UserCompetitionRepository::class);
        $this->app->bind(UserCompetitionService::class, function ($app) {
            return new UserCompetitionService(
                $app->make(UserCompetitionRepositoryInterface::class),
                $app->make(TransactionManagerInterface::class),
                $app->make(FlasherInterface::class)
            );
        });

        // auditing
        $this->app->bind(AuditRepositoryInterface::class, AuditRepository::class);
        $this->app->bind(AuditService::class, function ($app) {
            return new AuditService(
                $app->make(AuditRepositoryInterface::class),
                $app->make(TransactionManagerInterface::class),
                $app->make(FlasherInterface::class)
            );
        });

        // global questions
        $this->app->bind(GlobalQuestionRepositoryInterface::class, GlobalQuestionRepository::class);
        $this->app->bind(GlobalQuestionService::class, function ($app) {
            return new GlobalQuestionService($app->make(GlobalQuestionRepositoryInterface::class));
        });

        // user global questions
        $this->app->bind(UserGuestRepositoryInterface::class, UserGuestRepository::class);
        $this->app->bind(UserGuestService::class, function ($app) {
            return new UserGuestService($app->make(UserGuestRepositoryInterface::class));
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Implicitly grant "Super-Admin" role all permission checks using can()
        Gate::before(function ($user, $ability) {
            return $user->hasRole('owner') ? true : null;
        });

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

        // event listeners
        Event::listen(
            JobRetriedSuccessfully::class, 
            HandleAdminDeletionAfterJobSuccess::class
        );
    }
}
