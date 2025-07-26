<?php

namespace App\Http\Controllers\GuestUsers;

use Illuminate\Contracts\View\View;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use App\Services\GuestUsers\UserGuestService;
use App\Http\Requests\GuestUsers\StoreResponseRequest;

// The controller delegates all business logic to the service layer.
class UserGuestController extends Controller
{
    public function __construct(
        protected UserGuestService $userGuestService
    ) {
    }

    public function index(): View
    {
        return view('pages.user.guest_users.prepare');
    }

    public function getRandomQuestion():  View|RedirectResponse
    {
        $result =  $this->userGuestService->getRandomQuestion();

        if($result['status'] === 'empty_question'){
            return view('pages.user.guest_users.no_question');
        }elseif($result['status'] === 'error'){
            return redirect()->back();
        }else{
            $question = $result['question'];
            return view('pages.user.guest_users.question_response', compact('question'));
        }
    }

    function storeResponse(StoreResponseRequest $request): View|RedirectResponse
    {

        $result =  $this->userGuestService->storeResponse($request->validated());
        if($result['status'] === 'success'){
            $data_result = $result['data_result'];
            return view('pages.user.guest_users.response_score', compact('data_result'));
        }else{
            return redirect()->route('global_questions.index');
        }
    }

    function globalUsersOrder() : View
    {
        $users_data = $this->userGuestService->globalUsersOrder();
        return view('pages.user.guest_users.global_order', $users_data);
    }

    function getGlobalUserResponse() : View|RedirectResponse
    {
        $result = $this->userGuestService->getGlobalUserResponse();
        if($result['status'] === 'success'){
            $questions = $result['questions'];
            return view('pages.user.guest_users.user_global_responses', compact('questions'));
        }else{
            return redirect()->back();
        }
    }
}
