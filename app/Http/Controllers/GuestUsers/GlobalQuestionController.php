<?php

namespace App\Http\Controllers\GuestUsers;

use App\Http\Controllers\Controller;
use App\Http\Requests\GuestUsers\StoreQuestionRequest;
use App\Models\GuestUsers\GlobalQuestion;
use App\Services\GuestUsers\GlobalQuestionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class GlobalQuestionController extends Controller
{

    public function __construct(
        protected GlobalQuestionService $questionService
    ) {
    }

    /**
     * return all global questions view.
     * @return View
     */
    function all() : View{
        return $this->questionService->all();
    }

    /**
     * Handles the storing of a global questions request and returns a response with notifications.
     *
     * @param StoreQuestionRequest $request The incoming request containing admin data.
     */
    function store(StoreQuestionRequest $request): RedirectResponse
    {
        $this->questionService->create($request->all());
        return Redirect::back();
    }

    /**
     * Handles the improvement of a question request and returns a response with notifications.
     *
     * @param Request $request The incoming request containing admin data.
     */
    function approve(Request $request): RedirectResponse
    {
        $question = GlobalQuestion::findorfail(base64_decode($request->id));
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
        $question = GlobalQuestion::findorfail(base64_decode($request->id));
        $this->questionService->delete($question);
        return Redirect::back();
    }
}
