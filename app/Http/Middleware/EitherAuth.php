<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class EitherAuth
{
    public function handle($request, Closure $next)
    {
        if (Auth::guard('web')->check() || Auth::guard('admin')->check()) {
            return $next($request);
        }
        // Redirect to login (choose which one, or show a generic error)
        return redirect()->route('login');
    }
} 