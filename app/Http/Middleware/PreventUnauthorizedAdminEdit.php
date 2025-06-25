<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Admin\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PreventUnauthorizedAdminEdit
{
    public function handle(Request $request, Closure $next): Response
    {
        
        $authUser = Auth::user()->loadMissing('roles');

        $targetUser = Admin::with('roles')->find($request->id);

        if (!$targetUser) {
            return abort(404);
        }

        $roleHierarchy = config('roles.hierarchy');

        $currentUserRole = $authUser->roles->pluck('name')->first() ?? "null";
        $targetUserRole = $targetUser->roles
                        ->pluck('name')
                        ->sortBy(fn($r) => $roleHierarchy[$r] ?? PHP_INT_MAX)
                        ->first() ?? 'null';

        $currentPriority = $roleHierarchy[$currentUserRole] ?? PHP_INT_MAX;
        $targetPriority  = $roleHierarchy[$targetUserRole] ?? PHP_INT_MAX;

        // Redirect if trying to edit self or trying to edit someone with higher or equal privilege
        if (
            $authUser->id === $targetUser->id ||
            $currentPriority >= $targetPriority
        ) {
            Log::warning("Unauthorized admin try to edit or update another admin", [
                'admin_id' => $authUser->id,
                'target_admin_id' => $targetUser->id,
                'ip' => request()->ip(),
                'reason' => 'Admin not authorized to edit this admin',
            ]);
            return redirect()->route('admin.profile.edit');
        }

        $request->merge(['admin' => $targetUser]);

        return $next($request);
    }
}
