<?php

namespace App\View\composer;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SideBarComposer extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Using a Closure based composer...
        View::composer('layouts.admin.sidebar', function ($view) {
            $adminId = Auth::id(); // Get the authenticated admin's ID

            // Query to count users assigned to the auth admin who have responses with admin_id == null
            $assignedUserCount = DB::table('users')
                ->join('level_admin_user', 'users.id', '=', 'level_admin_user.user_id')
                ->join('responses', 'users.id', '=', 'responses.user_id')
                ->join('questions', 'responses.question_id', '=', 'questions.id')
                ->where('level_admin_user.admin_id', $adminId)
                ->whereColumn('level_admin_user.level_id', 'questions.level_id')  // Ensure levels match
                ->whereNull('responses.admin_id')
                ->distinct('users.id')
                ->count('users.id');

            // Pass the count to the sidebar view
            $view->with('assignedUserCount', $assignedUserCount);
        });
    }
}

