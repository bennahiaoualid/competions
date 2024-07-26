<?php

namespace App\Providers;

use App\Interface\Admin\AdminProfileRepositoryInterface;
use App\Interface\Admin\AdminRepositoryInterface;
use App\Interface\User\UserRepositoryInterface;
use App\Models\Admin\Admin;
use App\Models\User;
use App\Repository\Admin\AdminProfileRepository;
use App\Repository\Admin\AdminRepository;
use App\Repository\User\UserRepository;
use App\Services\Admin\AdminProfileService;
use App\Services\Admin\AdminService;
use App\Services\User\UserService;
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
    }
}
