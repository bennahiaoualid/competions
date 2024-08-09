<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Competition\FilterCompetitionRequest;
use App\Services\User\UserCompetitionService;
use Illuminate\View\View;


class UserCompetitionController extends Controller
{
    public function __construct(
        protected UserCompetitionService $userCompetitionService
    ) {
    }

    function all(FilterCompetitionRequest $request) : View{
        return $this->userCompetitionService->all($request->validated());
    }

}
