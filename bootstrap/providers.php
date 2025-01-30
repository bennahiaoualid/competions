<?php

use App\Providers\AuthServiceProvider;

return [
    App\Providers\AppServiceProvider::class,
    App\View\composer\SideBarComposer::class,
    AuthServiceProvider::class,
];
