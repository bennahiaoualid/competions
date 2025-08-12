<?php

namespace App\View\composer;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use App\Models\Payment\CoinBalance;

class UserBalanceComposer extends ServiceProvider
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
        // Simple composer to get current auth user balance
        View::composer('*', function ($view) {
            $user = Auth::user();
            
            if ($user) {
                $cacheKey = "user_balance_{$user->id}";
                
                // Get user's coin balance with caching
                $userBalance = cache()->remember($cacheKey, now()->addMinutes(30), function () use ($user) {
                    return CoinBalance::where('balanceable_id', $user->id)
                        ->where('balanceable_type', get_class($user))
                        ->first();
                });

                // Simple balance value
                $balance = $userBalance ? $userBalance->balance : 0;
                
                // Pass only the balance to the view
                $view->with('userBalance', $balance);
            } else {
                // No authenticated user, pass 0
                $view->with('userBalance', 0);
            }
        });
    }
} 