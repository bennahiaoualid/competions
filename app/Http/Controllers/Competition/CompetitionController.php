<?php

namespace App\Http\Controllers\Competition;

use Illuminate\View\View;
use App\Models\Admin\Admin;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use App\Services\SystemSettingService;
use App\Models\Competition\Competition;
use Illuminate\Support\Facades\Redirect;
use App\Services\Competition\CompetitionService;
use App\Http\Requests\Competition\StoreCompetitionRequest;
use App\Http\Requests\Competition\UpdateCompetitionRequest;
use App\Models\SystemSetting;

class CompetitionController extends Controller
{
    public function __construct(
        protected CompetitionService $competitionService,
        protected SystemSettingService $systemSettingService
    ) {
    }

    /**
     * display competitions list.
     * @return view
     */
    public function index(): View
    {
        $competitionGift = $this->systemSettingService->getValueAsInt('min_competition_coins');
        $secondPlacePercentage = $this->systemSettingService->getValueAsInt('second_place_winner_percentage', 50);
        $thirdPlacePercentage = $this->systemSettingService->getValueAsInt('third_place_winner_percentage', 20);
        // Get current admin's coin balance
        $userBalance = Auth::user()->coinBalance->balance ?? 0;
        
        return view("pages.admin.competitions.competition_list", compact(
            'competitionGift',
            'secondPlacePercentage', 
            'thirdPlacePercentage',
            'userBalance'
        ));
    }

    /**
     * Handles the storage of a competition request and returns a response with notifications.
     *
     * @param StoreCompetitionRequest $request The incoming request containing admin data.
     * @return RedirectResponse
     */
    function store(StoreCompetitionRequest $request): RedirectResponse
    {
        $this->competitionService->createCompetition($request->validated());
        return Redirect::back();
    }

    /**
     * Handles the editing of a competition request and returns a response with notifications.
     *
     * @param string $id_b64 The ID of the competition to edit.
     * @return View
     */
    function edit(string $id_b64): View
    {
        $competition = $this->competitionService->findCompetitionById($id_b64);
        if (!$competition) {
            abort(404, 'Competition not found.');
        }
        $admins = Admin::availableAsLevelManager()->get();
        
        // Get current admin's coin balance for edit form
        $userBalance = Auth::user()->coinBalance->balance ?? 0;
        
        return view("pages.admin.competitions.edit.competition_edit", compact("competition", "admins", "userBalance"));
    }

    /**
     * Handles the updating of a competition request and returns a response with notifications.
     *
     * @param UpdateCompetitionRequest $request The incoming request containing admin data.
     * @param Competition $competition The competition instance resolved by route model binding.
     * @return RedirectResponse
     */
    function update(UpdateCompetitionRequest $request, Competition $competition) : RedirectResponse {
        $this->competitionService->updateCompetition($competition, $request->validated());
        return Redirect::back();
    }


    /**
     * Handles the deleting of a competition request and returns a response with notifications.
     *
     * @param Request $request The incoming request containing admin data.
     * @return RedirectResponse
     */
    function delete(Request $request) : RedirectResponse {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $this->competitionService->deleteCompetition($validated['id']);
        return Redirect::back();
    }

    /**
     * navigate to view that display the the users belong to a competition.
     * @param string $competition_id_b64
     * @return view
     */
    function getCompetitionUsers(string $competition_id_b64) : view
    {
        $competition = $this->competitionService->findCompetitionById($competition_id_b64);
        if (!$competition) {
            abort(404, 'Competition not found.');
        }
        return view("pages.admin.competitions.competition_users", compact("competition"));
    }

    /**
     * Handles the adding of a users to a competition request and returns a response with notifications.
     *
     * @param Request $request The incoming request containing admin data.
     * @param Competition $competition The competition instance resolved by route model binding.
     * @return RedirectResponse
     */
    function addCompetitionUsers(Request $request, Competition $competition) : RedirectResponse {
        if ($request->user_ids) {
            $this->competitionService->addCompetitionUsers($competition, explode(",", $request->user_ids));
        }
        return Redirect::back();
    }

    /**
     * Handles the deleting of a user who belong to this competition request and returns a response with notifications.
     *
     * @param Request $request The incoming request containing admin data.
     * @return RedirectResponse
     */
    function removeCompetitionUser(Request $request) : RedirectResponse {
        $validated = $request->validate([
            'competition_id' => 'required|integer',
            'user_id' => 'required|integer',
        ]);
        $competition = $this->competitionService->findCompetitionById($validated['competition_id'], base64:false);
        if (!$competition) {
            abort(404, 'Competition not found.');
        }
        $this->competitionService->removeCompetitionUser($competition, $validated['user_id']);
        return Redirect::back();
    }

    /**
     * navigate to view that display the auditors  belong to a competition.
     * @param string $competition_id_b64
     * @return view
     */
    function getCompetitionAuditors($competition_id_b64) : view
    {
        $competition = $this->competitionService->findCompetitionById($competition_id_b64);
        if (!$competition) {
            abort(404, 'Competition not found.');
        }
        return view("pages.admin.competitions.competition_auditors", compact("competition"));
    }

    /**
     * Handles the adding of a auditors to a competition request and returns a response with notifications.
     *
     * @param Request $request The incoming request containing admin data.
     * @param Competition $competition The competition instance resolved by route model binding.
     * @return RedirectResponse
     */
    function addCompetitionAuditors(Request $request, Competition $competition) : RedirectResponse {
        if ($request->auditor_ids) {
            $this->competitionService->requestAuditorAssignment($competition, explode(",", $request->auditor_ids));
        }
        return Redirect::back();
    }

    /**
     * Handles the deleting of a auditor who belong to this competition request and returns a response with notifications.
     *
     * @param Request $request The incoming request containing admin data.
     * @return RedirectResponse
     */
    function removeCompetitionAuditor(Request $request) : RedirectResponse {
        $validated = $request->validate([
            'competition_id' => 'required|integer',
            'auditor_id' => 'required|integer',
        ]);
        $competition = $this->competitionService->findCompetitionById($validated['competition_id'], base64:false);
        if (!$competition) {
            abort(404, 'Competition not found.');
        }
        $this->competitionService->removeCompetitionAuditor($competition, $validated['auditor_id']);
        return Redirect::back();
    }


    /**
     * @param Request $request The incoming request containing competition_id.
     * @param Competition $competition The competition instance resolved by route model binding.
     * @return RedirectResponse
     */
    function activateCompetition(Request $request, Competition $competition): RedirectResponse
    {
        $this->competitionService->activateCompetition($competition);
        return Redirect::back();
    }
}
