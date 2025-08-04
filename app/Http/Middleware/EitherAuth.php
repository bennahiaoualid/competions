<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class EitherAuth
{
    public function handle($request, Closure $next)
    {
        if (Auth::guard('web')->check()) {
            // Set web as default guard for this request
            Auth::shouldUse('web');
            return $next($request);
        }
        
        if (Auth::guard('admin')->check()) {
            // Set admin as default guard for this request
            Auth::shouldUse('admin');
            return $next($request);
        }
        
        // Neither guard is authenticated
        return redirect()->route('login');
    }
}