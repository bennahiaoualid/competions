<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class GuestWithGuard
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param string|null $guard
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $guard = null)
    {
        // Check if the user is authenticated with the specified guard
        $admin = Auth::guard("admin")->check();
        $user = Auth::guard("web")->check();
        //if($admin || Auth::guard("web")->check()){
           // dd(Auth::guard(),$guard,Auth::guard($guard)->check(),Auth::guard()->check());
            if ($user) {
                // Redirect the authenticated user to their home page or dashboard
                return redirect('/profile');
            }
            elseif ($admin) {
                // Redirect the authenticated user to their home page or dashboard
                return redirect('/admin/');
            }
        //}
        return $next($request);
    }
}
