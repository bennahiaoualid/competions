<?php

namespace App\Http\Controllers\Competition;

use App\Http\Controllers\Controller;
use App\Http\Requests\Competition\StoreCompetitionRequest;
use App\Http\Requests\Competition\UpdateCompetitionRequest;
use App\Models\Admin\Admin;
use App\Models\Competition\Competition;
use App\Services\Competition\CompetitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class CompetitionController extends Controller
{
    public function __construct(
        protected CompetitionService $competitionService
    ) {
    }

    /**
     * display competitions list.
     */
    public function index(): View
    {
        return view("pages.admin.competitions.competition_list");
    }

    /**
     * Handles the storage of a competition request and returns a response with notifications.
     *
     * @param StoreCompetitionRequest $request The incoming request containing admin data.
     */
    function store(StoreCompetitionRequest $request): RedirectResponse
    {
        $this->competitionService->createCompetition($request->validated());
        return Redirect::back();
    }

    function edit($id): View
    {
        $competition = $this->competitionService->findCompetitionById($id);
        if (!$competition) {
            abort(404, 'Competition not found.');
        }
        $admins = Admin::all();
        return view("pages.admin.competitions.edit.competition_edit", compact("competition", "admins"));
    }

    /**
     * Handles the updating of a competition request and returns a response with notifications.
     *
     * @param UpdateCompetitionRequest $request The incoming request containing admin data.
     * @param Competition $competition The competition instance resolved by route model binding.
     */
    function update(UpdateCompetitionRequest $request, Competition $competition) : RedirectResponse {
        $this->competitionService->updateCompetition($competition, $request->validated());
        return Redirect::back();
    }


    /**
     * Handles the deleting of a competition request and returns a response with notifications.
     *
     * @param Request $request The incoming request containing admin data.
     */
    function delete(Request $request) : RedirectResponse {
        $this->competitionService->deleteCompetition($request->id);
        return Redirect::back();
    }

    /**
     * navigate to view that display the the users belong to a competition.
     */
    function getCompetitionUsers($competition_id_b64) : view
    {
        $competition = $this->competitionService->getCompetitionUsers($competition_id_b64);
        if (!$competition) {
            abort(404, 'Competition not found.');
        }
        return view("pages.admin.competitions.competition_users", compact("competition"));
    }

    /**
     * Handles the deleting of a user who belong to this competition request and returns a response with notifications.
     *
     * @param Request $request The incoming request containing admin data.
     */
    function removeCompetitionUser(Request $request) : RedirectResponse {
        $this->competitionService->removeCompetitionUser((int)$request->competition_id, (int)$request->user_id);
        return Redirect::back();
    }

    /**
     * Handles the adding of a users to a competition request and returns a response with notifications.
     *
     * @param Request $request The incoming request containing admin data.
     */
    function addCompetitionUsers(Request $request) : RedirectResponse {
        if ($request->user_ids) {
            $this->competitionService->addCompetitionUsers((int)$request->competition_id, explode(",", $request->user_ids));
        }
        return Redirect::back();
    }

    /**
     * navigate to view that display the auditors  belong to a competition.
     */
    function getCompetitionAuditors($competition_id_b64) : view
    {
        $competition = $this->competitionService->getCompetitionAuditors($competition_id_b64);
        if (!$competition) {
            abort(404, 'Competition not found.');
        }
        return view("pages.admin.competitions.competition_auditors", compact("competition"));
    }

    /**
     * Handles the adding of a auditors to a competition request and returns a response with notifications.
     *
     * @param Request $request The incoming request containing admin data.
     */
    function addCompetitionAuditors(Request $request) : RedirectResponse {
        if ($request->auditor_ids) {
            $this->competitionService->addCompetitionAuditors((int)$request->competition_id, explode(",", $request->auditor_ids));
        }
        return Redirect::back();
    }

    /**
     * Handles the deleting of a auditor who belong to this competition request and returns a response with notifications.
     *
     * @param Request $request The incoming request containing admin data.
     */
    function removeCompetitionAuditor(Request $request) : RedirectResponse {
        $this->competitionService->removeCompetitionAuditor((int)$request->competition_id, (int)$request->auditor_id);
        return Redirect::back();
    }

    /**
     * @param Request $request The incoming request containing competition_id.
     */
    function activateCompetition(Request $request): RedirectResponse
    {
        $this->competitionService->activateCompetition((int)$request->competition_id);
        return Redirect::back();
    }
}
