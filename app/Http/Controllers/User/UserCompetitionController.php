<?php

namespace App\Http\Controllers\User;

use Illuminate\View\View;
use App\Models\Competition\Level;
use App\Http\Controllers\Controller;
use App\Models\Competition\Question;
use Illuminate\Http\RedirectResponse;
use App\Models\Competition\Competition;
use App\Services\User\UserCompetitionService;
use App\Http\Requests\Competition\StoreResponseRequest;
use App\Http\Requests\Competition\FilterCompetitionRequest;

class UserCompetitionController extends Controller
{
    public function __construct(
        protected UserCompetitionService $userCompetitionService
    ) {
    }

    /**
     * Get all public competitions with optional filters
     * @param FilterCompetitionRequest $request
     * @return View
     */
    public function getAllPublicCompetitions(FilterCompetitionRequest $request): View
    {
        $competitions = $this->userCompetitionService->getAllPublicCompetitions($request->validated());
        return view('pages.user.competitions', compact('competitions'));
    }   

    /**
     * Get user competitions with optional filters
     * @param FilterCompetitionRequest $request
     * @return View
     */
    public function getUserCompetitions(FilterCompetitionRequest $request): View
    {
        $competitions = $this->userCompetitionService->getUserCompetitions($request->validated());
        return view('pages.user.competitions', compact('competitions'));
    }


    /**
     * Display competition details
     * @param Competition $competition
     * @return View
     */
    public function competitionDetail(Competition $competition): View
    {
        $data = $this->userCompetitionService->competitionDetail($competition);
        return view('pages.user.competition_detail', $data);
    }

    /**
     * Display level details
     * @param Level $level
     * @return View
     */
    public function levelDetail(Level $level): View
    {
        $data = $this->userCompetitionService->levelDetail($level);
        return view('pages.user.level_detail', $data);
    }

    /**
     * Start a competition level
     * @param Level $level
     * @return View|RedirectResponse
     */
    public function levelStart(Level $level): View|RedirectResponse
    {
        $request_result =  $this->userCompetitionService->levelStart($level);
        if($request_result['status'] == 'success'){
            return view('pages.user.question_response', $request_result);
        }
        elseif($request_result['status'] == 'empty'){
            return redirect()->route('user.competitions.response', ['level' => $request_result['level']]);
        }
        else{
            return redirect()->back();
        }

    }

    /**
     * Store user's response to a question
     * @param StoreResponseRequest $request
     * @param Question $question
     * @return RedirectResponse
     */
    public function storeResponse(StoreResponseRequest $request, Question $question): RedirectResponse
    {
        $request_result = $this->userCompetitionService->storeResponse($question,$request->validated());
        if($request_result){
            return redirect()->route('user.competitions.level.response', ['level' => $question->level]);
        }
        else{
            return redirect()->back();
        }
    }

    /**
     * Display user's responses for a level
     * @param Level $level
     * @return View
     */
    public function userResponses(Level $level): View
    {
        $data = $this->userCompetitionService->userResponses($level);
        return view('pages.user.user_responses_list', $data);
    }

    /**
     * Display competitors order for a level
     */
    public function competitorsLevelOrder(Level $level): View
    {
        $data = $this->userCompetitionService->competitorsLevelOrder($level);
        return view('pages.user.competitors_level_order', $data);
    }

    /**
     * Display competitors order for a competition
     * @param Competition $competition
     * @return View
     */
    public function competitorsCompetitionOrder(Competition $competition): View
    {
        $data = $this->userCompetitionService->competitorsCompetitionOrder($competition);
        return view('pages.user.competitors_competition_order', $data);
    }
}
