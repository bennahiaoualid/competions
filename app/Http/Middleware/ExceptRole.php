<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExceptRole
{
    public function handle(Request $request, Closure $next, $roles, $guard = null)
    {
        if (auth()->guard($guard)->check()) {
            $user = app('auth')->guard($guard)->user();
            
            // Convert roles string to array if needed
            $roleArray = is_string($roles) ? explode('|', $roles) : [$roles];
            
            // Check if user has any of the excluded roles
            foreach ($roleArray as $role) {
                if ($user->hasRole(trim($role))) {
                    abort(403, 'Access denied: This action is not allowed.');
                }
            }
        }

        return $next($request);
    }
}
