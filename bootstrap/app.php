<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',

    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'localize'                => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes::class,
            'localizationRedirect'    => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter::class,
            'localeSessionRedirect'   => \Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect::class,
            'localeCookieRedirect'    => \Mcamara\LaravelLocalization\Middleware\LocaleCookieRedirect::class,
            'localeViewPath'          => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'prevent_unauthorized_admin_edit' => \App\Http\Middleware\PreventUnauthorizedAdminEdit::class,
            'can_delete_admin' => \App\Http\Middleware\EnsureAdminCanDeleteAdmin::class,
            'can_delete_user' => \App\Http\Middleware\EnsureAdminCanDeleteUser::class,
            'can_update_competition' => \App\Http\Middleware\UpdateCompetition::class,
            'guest.guard' => \App\Http\Middleware\GuestWithGuard::class,
            'auth_competitor' =>\App\Http\Middleware\AuthCompetitor::class,
            'either.auth' => \App\Http\Middleware\EitherAuth::class,
            'not_allowed_roles' => \App\Http\Middleware\ExceptRole::class,

        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
