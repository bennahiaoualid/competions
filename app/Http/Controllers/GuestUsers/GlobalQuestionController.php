<?php

namespace App\Http\Controllers\GuestUsers;

use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use App\Models\GuestUsers\GlobalQuestion;
use App\Services\GuestUsers\GlobalQuestionService;
use App\Http\Requests\GuestUsers\StoreQuestionRequest;
use App\Services\Competition\GlobalQuestionGenerationService;

class GlobalQuestionController extends Controller
{

    public function __construct(
        protected GlobalQuestionService $questionService,
    ) {
    }

    /**
     * return all global questions view.
     * @return View
     */
    function all() : View{
        return view("pages.admin.guest_users.global_question_list");
    }

    /**
     * Handles the storing of a global questions request and returns a response with notifications.
     *
     * @param StoreQuestionRequest $request The incoming request containing admin data.
     */
    function store(StoreQuestionRequest $request): RedirectResponse
    {
        $this->questionService->create($request->validated());
        return Redirect::back();
    }

    /**
     * Handles the improvement of a question request and returns a response with notifications.
     *
     * @param Request $request The incoming request containing admin data.
     */
    function approve(Request $request): RedirectResponse
    {
        $question = GlobalQuestion::findorfail($request->id);
        $this->questionService->approve($question);
        return Redirect::back();
    }

    /**
     * Handles the deleting of a question request and returns a response with notifications.
     *
     * @param Request $request The incoming request containing admin data.
     */
    function delete(Request $request): RedirectResponse
    {
        $question = GlobalQuestion::findorfail($request->id);
        $this->questionService->delete($question);
        return Redirect::back();
    }
}
