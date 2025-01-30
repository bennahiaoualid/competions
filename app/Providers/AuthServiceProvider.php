<?php

namespace App\Providers;


use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

use Illuminate\Support\Facades\Auth;


class AuthServiceProvider extends ServiceProvider

{

    public function boot(): void

    {

        Auth::provider('all-users', function ($app, $config) {

            return new AllUsersProvider($app['hash'], $config['model']);

        });

    }

}
