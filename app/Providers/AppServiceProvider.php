<?php

namespace App\Providers;

use App\Interface\Admin\AdminProfileRepositoryInterface;
use App\Interface\Admin\AdminRepositoryInterface;
use App\Interface\Competition\CompetitionRepositoryInterface;
use App\Interface\Competition\LevelRepositoryInterface;
use App\Interface\Competition\QuestionRepositoryInterface;
use App\Interface\User\UserCompetitionRepositoryInterface;
use App\Interface\User\UserRepositoryInterface;
use App\Models\Admin\Admin;
use App\Models\User;
use App\Repository\Admin\AdminProfileRepository;
use App\Repository\Admin\AdminRepository;
use App\Repository\Competition\CompetitionRepository;
use App\Repository\Competition\LevelRepository;
use App\Repository\Competition\QuestionRepository;
use App\Repository\User\UserCompetitionRepository;
use App\Repository\User\UserRepository;
use App\Services\Admin\AdminProfileService;
use App\Services\Admin\AdminService;
use App\Services\Competition\CompetitionService;
use App\Services\Competition\LevelService;
use App\Services\Competition\QuestionService;
use App\Services\User\UserCompetitionService;
use App\Services\User\UserService;
use Carbon\Carbon;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
            return new AdminService($app->make(AdminRepositoryInterface::class));
        });

        // user
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(UserService::class, function ($app) {
            return new UserService($app->make(UserRepositoryInterface::class));
        });

        // competition
        $this->app->bind(CompetitionRepositoryInterface::class, CompetitionRepository::class);
        $this->app->bind(CompetitionService::class, function ($app) {
            return new CompetitionService($app->make(CompetitionRepositoryInterface::class));
        });

        // level
        $this->app->bind(LevelRepositoryInterface::class, LevelRepository::class);
        $this->app->bind(LevelService::class, function ($app) {
            return new LevelService($app->make(LevelRepositoryInterface::class));
        });

        // question
        $this->app->bind(QuestionRepositoryInterface::class, QuestionRepository::class);
        $this->app->bind(QuestionService::class, function ($app) {
            return new QuestionService($app->make(QuestionRepositoryInterface::class));
        });

        // user competition
        $this->app->bind(UserCompetitionRepositoryInterface::class, UserCompetitionRepository::class);
        $this->app->bind(UserCompetitionService::class, function ($app) {
            return new UserCompetitionService($app->make(UserCompetitionRepositoryInterface::class));
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
              return url('') . 'reset-password' . '?token=' . $token;
        });

        Carbon::macro('inUserTimezone', function() {
            return $this->tz(session()->get('timezone') ?? config('app.timezone_display'));
        });
    }
}
