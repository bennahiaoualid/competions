<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Competition\FilterCompetitionRequest;
use App\Http\Requests\Competition\StoreResponseRequest;
use App\Services\User\UserCompetitionService;
use http\Client\Request;
use Illuminate\View\View;


class UserCompetitionController extends Controller
{
    public function __construct(
        protected UserCompetitionService $userCompetitionService
    ) {
    }

    function all(FilterCompetitionRequest $request,$user = null) : View{
        return $this->userCompetitionService->all($request->validated(),$user);
    }

    function competitionDetail($competition_id) : View{
        return $this->userCompetitionService->competitionDetail($competition_id);
    }

    function levelDetail($level_id) : View{
        return $this->userCompetitionService->levelDetail($level_id);
    }

    function levelStart($level_id): \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
    {
        return $this->userCompetitionService->levelStart($level_id);
    }

    function storeResponse(StoreResponseRequest $request): \Illuminate\Http\RedirectResponse
    {
        return $this->userCompetitionService->storeResponse($request->validated());
    }

    function userResponses($level_id) : View{
        return $this->userCompetitionService->userResponses($level_id);
    }

    function competitorsLevelOrder($level_id) : View{
        return $this->userCompetitionService->competitorsLevelOrder($level_id);
    }

    function competitorsCompetitionOrder($competition_id) : View{
        return $this->userCompetitionService->competitorsCompetitionOrder($competition_id);
    }
}
