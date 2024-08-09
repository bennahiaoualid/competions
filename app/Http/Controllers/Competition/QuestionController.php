<?php

namespace App\Http\Controllers\Competition;

use App\Http\Controllers\Controller;
use App\Http\Requests\Competition\StoreCompetitionRequest;
use App\Http\Requests\Competition\StoreQuestionRequest;
use App\Http\Requests\Competition\UpdateCompetitionRequest;
use App\Http\Requests\Competition\UpdateQuestionRequest;
use App\Models\Competition\Competition;
use App\Models\Competition\Question;
use App\Services\Competition\CompetitionService;
use App\Services\Competition\QuestionService;
use App\Traits\CrudOperationNotificationAlert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class QuestionController extends Controller
{
    use CrudOperationNotificationAlert;
    public function __construct(
        protected QuestionService $questionService
    ) {
    }

    /**
     * Handles the updating of a level request and returns a response with notifications.
     * @return View
     */
    function all($level_id) : View{
        return $this->questionService->all($level_id);
    }

    /**
     * Handles the storing of a questions request and returns a response with notifications.
     *
     * @param StoreQuestionRequest $request The incoming request containing admin data.
     */
    function store(StoreQuestionRequest $request): RedirectResponse
    {
        $this->questionService->create($request->all());
        return Redirect::back();
    }

    /**
     * Handles the updating of a question request and returns a response with notifications.
     *
     * @param UpdateQuestionRequest $request The incoming request containing admin data.
     */
    function update(UpdateQuestionRequest $request) : RedirectResponse {
        $question = Question::findorfail($request->id);
        $this->questionService->update($question, $request->validated());
        return Redirect::back();
    }
}
