<?php

namespace App\Http\Middleware;

use App\Models\Competition\Competition;
use App\Traits\CrudOperationNotificationAlert;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class UpdateCompetition
{
    use CrudOperationNotificationAlert;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $competition = $request->route('competition');

        // Ensure $competition is an instance of Competition, otherwise it might be null if resolution failed
        // or if the route parameter name is different. Route model binding handles failure by 404 automatically.
        if (!$competition instanceof Competition) {
            // This case should ideally not be hit if route model binding is set up correctly
            // and the parameter name matches. Consider how to handle if it does.
            return redirect()->back()->with(
                [
                    "messages" => $this->generateCustomNotifications(__('Something went wrong.'),"error")
                ]
            );
        }

        $routeName = Route::currentRouteName();

        // only the user who created the competition can update it
        if ($competition->admin_id !== Auth::guard('admin')->id()){
            return redirect()->back()->with(
                [
                    "messages" => $this->generateCustomNotifications(__('messages.validation.not_allow.competition_update'),"error")
                ]
            ) ;
        }
        // only the competition that not activated yet can be updated
        elseif ($competition->status != 0 && $routeName != "admin.competitions.level.update"){
            return redirect()->back()->with(
                [
                    "messages" => $this->generateCustomNotifications(__('messages.validation.not_allow.active_competition_update'),"error")
                ]
            ) ;
        }
        return $next($request);
    }
}
