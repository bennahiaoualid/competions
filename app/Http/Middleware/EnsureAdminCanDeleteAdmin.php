<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Admin\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Traits\CrudOperationNotificationAlert;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminCanDeleteAdmin
{
    use CrudOperationNotificationAlert;

    public function handle(Request $request, Closure $next): Response
    {
        $currentAdmin = Auth::user();
        $deletedAdmin = Admin::findOrFail($request->id);
        
        $canDelete = ($currentAdmin->hasRole('owner') ||
                    ($deletedAdmin->admin_id && $deletedAdmin->admin_id === $currentAdmin->id))
                    && $deletedAdmin->id !== $currentAdmin->id;

        if (!$canDelete) {
            $notification = $this->generateCustomNotification(__('messages.validation.not_allow.admin_delete'),"error");
            // Flash each message to the session
            session()->flash('messages', session('messages', collect())->push($notification));
            return redirect()->back();
        }

        $request->merge(['admin' => $deletedAdmin]);
        return $next($request);
    }
}
