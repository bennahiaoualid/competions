<?php

namespace App\Http\Controllers\Competition;

use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Models\Competition\Level;
use App\Http\Controllers\Controller;
use App\Models\Competition\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use App\Services\Competition\QuestionService;
use App\Traits\CrudOperationNotificationAlert;
use App\Http\Requests\Competition\StoreQuestionRequest;
use App\Http\Requests\Competition\UpdateQuestionRequest;

class QuestionController extends Controller
{
    use CrudOperationNotificationAlert;
    public function __construct(
        protected QuestionService $questionService
    ) {
    }

    /**
     * Get all questions for a specific level
     *
     * @param string $level_id_base64 Base64 encoded level ID
     * @param Request $request The incoming request containing admin data.
     * @return View
     */
    function all(string $level_id_base64, Request $request) : View{
        $perPage = $request->input('perPage', 5);
        return $this->questionService->all($level_id_base64, $perPage);
    }

    /**
     * Handles the storing of a questions request and returns a response with notifications.
     *
     * @param StoreQuestionRequest $request The incoming request containing admin data.
     * @param Level $level The level to store the questions for.
     * @return RedirectResponse
     */
    function store(StoreQuestionRequest $request, Level $level): RedirectResponse
    {
        $this->questionService->create($request->all(),$level);
        return Redirect::back();
    }

    /**
     * Handles the updating of a question request and returns a response with notifications.
     *
     * @param UpdateQuestionRequest $request The incoming request containing admin data.
     * @param Question $question The question to update.
     * @return RedirectResponse
     */
    function update(UpdateQuestionRequest $request, Question $question) : RedirectResponse {
        $this->questionService->update($question, $request->validated());
        return Redirect::back();
    }
}
