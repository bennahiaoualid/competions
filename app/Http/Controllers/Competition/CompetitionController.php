<?php

namespace App\Http\Controllers\Competition;

use App\Http\Controllers\Controller;
use App\Http\Requests\Competition\StoreCompetitionRequest;
use App\Http\Requests\Competition\UpdateCompetitionRequest;
use App\Models\Competition\Competition;
use App\Services\Competition\CompetitionService;
use App\Traits\CrudOperationNotificationAlert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class CompetitionController extends Controller
{
    use CrudOperationNotificationAlert;
    public function __construct(
        protected CompetitionService $competitionService
    ) {
    }

    /**
     * display competitions list.
     */
    public function all(){
        return $this->competitionService->all();
    }

    /**
     * Handles the storage of a competition request and returns a response with notifications.
     *
     * @param StoreCompetitionRequest $request The incoming request containing admin data.
     */
    function store(StoreCompetitionRequest $request): RedirectResponse
    {
        $this->competitionService->create($request->validated());
        return Redirect::back();
    }

    function edit($id){
        return $this->competitionService->edit($id);
    }

    /**
     * Handles the updating of a competition request and returns a response with notifications.
     *
     * @param UpdateCompetitionRequest $request The incoming request containing admin data.
     */
    function update(UpdateCompetitionRequest $request) : RedirectResponse {
        $competition = Competition::findorfail($request->id);
        $this->competitionService->update($competition, $request->validated());

        return Redirect::back();
    }


    /**
     * Handles the deleting of a competition request and returns a response with notifications.
     *
     * @param Request $request The incoming request containing admin data.
     */
    function delete(Request $request) : RedirectResponse {
        $competition = Competition::findorfail($request->id);
        $this->competitionService->delete($competition);

        return Redirect::back();
    }

    /**
     * navigate to view that display the the users belong to a competition.
     */
    function getCompetitionUsers($competition_id) : view{
        return $this->competitionService->getCompetitionUsers($competition_id);
    }

    /**
     * Handles the deleting of a user who belong to this competition request and returns a response with notifications.
     *
     * @param Request $request The incoming request containing admin data.
     */
    function removeCompetitionUser(Request $request) : RedirectResponse {
        $this->competitionService->removeCompetitionUser($request->competition_id, $request->user_id);
        return Redirect::back();
    }

    /**
     * Handles the adding of a users to a competition request and returns a response with notifications.
     *
     * @param Request $request The incoming request containing admin data.
     */
    function addCompetitionUsers(Request $request) : RedirectResponse {
        if ($request->user_ids) {
            $this->competitionService->addCompetitionUsers($request->competition_id, explode(",",$request->user_ids));
        }
        return Redirect::back();
    }

    /**
     * navigate to view that display the auditors  belong to a competition.
     */
    function getCompetitionAuditors($competition_id) : view{
        return $this->competitionService->getCompetitionAuditors($competition_id);
    }

    /**
     * Handles the adding of a auditors to a competition request and returns a response with notifications.
     *
     * @param Request $request The incoming request containing admin data.
     */
    function addCompetitionAuditors(Request $request) : RedirectResponse {
        if ($request->auditor_ids) {
            $this->competitionService->addCompetitionAuditors($request->competition_id, explode(",",$request->auditor_ids));
        }
        return Redirect::back();
    }

    /**
     * Handles the deleting of a auditor who belong to this competition request and returns a response with notifications.
     *
     * @param Request $request The incoming request containing admin data.
     */
    function removeCompetitionAuditor(Request $request) : RedirectResponse {
        $this->competitionService->removeCompetitionAuditor($request->competition_id, $request->auditor_id);
        return Redirect::back();
    }

    /**
     * @param Request $request The incoming request containing competition_id.
     */
    function activateCompetition(Request $request): RedirectResponse
    {
        $this->competitionService->activateCompetition($request->competition_id);
        return Redirect::back();
    }
}
