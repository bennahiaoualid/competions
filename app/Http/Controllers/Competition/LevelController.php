<?php

namespace App\Http\Controllers\Competition;

use App\Http\Controllers\Controller;
use App\Http\Requests\Competition\StoreLevelRequest;
use App\Http\Requests\Competition\UpdateLevelRequest;
use App\Models\Competition\Level;
use App\Services\Competition\LevelService;
use App\Traits\CrudOperationNotificationAlert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class LevelController extends Controller
{
    use CrudOperationNotificationAlert;
    public function __construct(
        protected LevelService $levelService
    ) {
    }

    /**
     * Handles the updating of a competition request and returns a response with notifications.
     *
     * @param StoreLevelRequest $request The incoming request containing admin data.
     */
    function store(StoreLevelRequest $request) : RedirectResponse {
        $this->levelService->create($request->all());
        return Redirect::back();
    }

    /**
     * navigate to view that display the level information.
     */
    function edit($id) : view{
        return $this->levelService->edit($id);
    }

    /**
     * Handles the updating of a level request and returns a response with notifications.
     *
     * @param UpdateLevelRequest $request The incoming request containing admin data.
     */
    function update(UpdateLevelRequest $request) : RedirectResponse {
        $level = Level::findorfail($request->id);
        $this->levelService->update($level, $request->validated());
        return Redirect::back();
    }



}
