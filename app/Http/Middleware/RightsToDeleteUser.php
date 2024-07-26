<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Traits\CrudOperationNotificationAlert;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RightsToDeleteUser
{
    use CrudOperationNotificationAlert;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $currentAdmin = Auth::user();
        $deletedUser = User::findorfail($request->id);
        // check if the current admin is not super
        if(!$currentAdmin->hasRole(['super_admin','owner'], 'admin')){
            // check if this admin is not the one who created this user before deleting
            if($deletedUser->admin_id != $currentAdmin->id or !$currentAdmin->can('delete user')){
                $notifications = $this->generateCustomNotifications(__('messages.validation.not_allow.user_delete'),"error");
                // Flash each message to the session
                foreach ($notifications as $notification) {
                    session()->flash('messages', session('messages', collect())->push($notification));
                }
                return redirect()->back();
            }
        }
        $request->merge(['user' => $deletedUser]);
        return $next($request);
    }
}
