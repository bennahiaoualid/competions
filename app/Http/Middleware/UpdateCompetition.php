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
        $competition = Competition::findorfail($request->competition_id??$request->id);
        $routeName = Route::currentRouteName();

        // only the user who created the competition can update it
        if ($competition->admin_id !== Auth::id()){
            return redirect()->back()->with(
                [
                    "messages" => $this->generateCustomNotifications(__('messages.validation.not_allow.competition_update'),"error")
                ]
            ) ;
        }
        // only the competition that not activated yet can be updated
        elseif ($competition->active != 0 && $routeName != "admin.competitions.level.update"){
            return redirect()->back()->with(
                [
                    "messages" => $this->generateCustomNotifications(__('messages.validation.not_allow.active_competition_update'),"error")
                ]
            ) ;
        }
        return $next($request);
    }
}
