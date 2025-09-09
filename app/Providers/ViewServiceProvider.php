<?php

namespace App\Providers;

use App\View\Composer\SidebarComposer;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class ViewServiceProvider extends ServiceProvider
{
    public function boot()
    {

        View::composer('layouts.admin.sidebar', SidebarComposer::class);
    }
}