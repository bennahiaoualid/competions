<?php

namespace App\Http\Middleware;

use App\Models\Admin\Admin;
use App\Traits\CrudOperationNotificationAlert;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RightsToDeleteAdmin
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
        $deletedAdmin = Admin::findorfail($request->id);
        // check if the current admin is not owner
        if (!$currentAdmin->hasRole('owner', 'admin')){
            // check if this admin is not the one who created this admin before deleting
            if($deletedAdmin->admin_id != $currentAdmin->id){
                $notifications = $this->generateCustomNotifications(__('messages.validation.not_allow.admin_delete'),"error");
                // Flash each message to the session
                foreach ($notifications as $notification) {
                    session()->flash('messages', session('messages', collect())->push($notification));
                }
                return redirect()->back();
            }
        }
        $request->merge(['admin' => $deletedAdmin]);
        return $next($request);
    }
}
