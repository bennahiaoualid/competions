<?php

namespace App\Http\Middleware;

use App\Models\Admin\Admin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthUserRedirectToProfile
{
    /**
     * Handle an incoming request.
     * check if the request id for the admin edit page is not the same id for the auth admin
     * check if the request id for the admin edit is not high priority the the auth admin
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $currentUserRole = Auth::user()->getRoleNames()->first();
        $targetUser = Admin::with('roles')->findOrFail($request->id);
        $targetUserRole = $targetUser->getRoleNames()->first() ?? "null";
        $roleHierarchy = config('roles.hierarchy');
        // Check if the current user has a higher priority than the target role or is the same
        if (
            ($request->id == Auth::id()) or
            ($roleHierarchy[$currentUserRole] >= $roleHierarchy[$targetUserRole])
        ){
            return redirect()->route('admin.profile.edit');
        }
        $request->merge(['admin' => $targetUser]);
        return $next($request);
    }
}
