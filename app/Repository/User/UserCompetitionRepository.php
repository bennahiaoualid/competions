<?php

namespace App\Repository\User;

use Exception;
use App\Models\User;
use Illuminate\View\View;
use App\Traits\RegisterLogs;
use App\Models\Competition\Level;
use App\Models\Competition\Question;
use App\Models\Competition\Response;
use Illuminate\Support\Facades\Auth;
use App\Helpers\CompetitionsOrder;
use App\Models\Competition\Competition;
use Illuminate\Database\Eloquent\Collection;
use App\Traits\CrudOperationNotificationAlert;
use App\Interface\User\UserCompetitionRepositoryInterface;
use App\Traits\Filterable;
class UserCompetitionRepository implements UserCompetitionRepositoryInterface
{
    use RegisterLogs, 
    CrudOperationNotificationAlert,
    Filterable;

    /**
     * Get all public competitions with optional filters
     * @param array $filters
     * @return Collection
     */
    public function getAllPublicCompetitions(array $filters = []): Collection
    {
        return $this->applyFilters(Competition::query(), $filters)
            ->orderByDesc('start_date')
            ->get();
    }

    /**
     * Get user competitions with optional filters
     * @param User $user
     * @param array $filters
     * @return Collection
     */
    public function getUserCompetitions(User $user, array $filters = []): Collection
    {
        return $this->applyFilters($user->competitions(), $filters)
            ->orderByDesc('start_date')
            ->get();
    }

    function competitionDetail($competition_id): View
    {
        $competition = Competition::with("levels")->findorfail(base64_decode($competition_id));
        $results = $audit_finish =CompetitionsOrder::getCompetitorsOrder($competition,true,true);
        $users = $results["users"];
        $audit_finish = $results["audit_finish"];
        // Return the view with competition data
        return view('pages.user.competition_detail', compact('competition','users','audit_finish'));
    }

    function levelDetail($level_id): View
    {
        $level = Level::findorfail(base64_decode($level_id));

        $results = $audit_finish = CompetitionsOrder::getCompetitorsOrder($level,true,false);
        $users = $results["users"];
        $audit_finish = $results["audit_finish"];
        return view('pages.user.level_detail', compact('level','users','audit_finish'));
    }

    function levelStart($level_id):\Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
    {
        $level_id = base64_decode($level_id);
        $level = Level::findorfail($level_id);

        $user = Auth::user();
        // get the question
        $questions = Question::where('level_id', $level_id)
            ->whereDoesntHave('responses', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            });
        $question = $questions->inRandomOrder()->first();
        $question_count = [
           "current" => $level->questions_number - $questions->count() +1 ,
            "all" => $level->questions_number
        ];

        if (!$question) {
            return redirect()->route('user.competitions.response', ['id' => base64_encode($level_id)]);
        }

        // init the response
       if (! $this->initResponse($question->id, $user->id)){
           return redirect()->back();
       }

        // Store the start time in the session
        session(['start_time' => now()]);

        return view('pages.user.question_response', compact('question',"question_count", 'level'));
    }

    public function storeResponse(array $data): \Illuminate\Http\RedirectResponse
    {
        try {
            $user = Auth::user();

            // Retrieve the start time and response ID from the session
            $startTime = session('start_time');

            // Calculate the response time
            $responseTime = $startTime->diffInUTCSeconds(now());

            // Update the response record
            $response = Response::where([
                'question_id' => $data['question_id'],
                'user_id' => $user->id,
            ])->first();
            $response->update([
                'response_text' => $data['response_text'] ?? '',
                'response_duration' => round($responseTime,2),
            ]);

            // Clear session data
            session()->forget(['start_time']);

            // get level id
            $level_id = Question::find($data['question_id'])->level_id;

            return redirect()->route('user.competitions.level.response', ['id' => base64_encode($level_id)]);

        }catch (Exception $exception){
            $this->registerLogs('ٌUser Response Store error: ',$exception);
            $notifications = $this->generateNotifications(false,"something_went_wrong");
            // Flash each message to the session
            foreach ($notifications as $notification) {
                session()->flash('messages', session('messages', collect())->push($notification));
            }
            return redirect()->back();
        }
    }

    /**
     * @param $question_id
     * @param $user_id
     * @return bool
     */
    private function initResponse($question_id, $user_id): bool
    {
        try {
            Response::create([
                'response_text' => '',
                'question_id' => $question_id,
                'user_id' => $user_id,
                'admin_id' => null,
            ]);
            return true;
        }catch (Exception $exception){
            $this->registerLogs('ٌResponse creation error: ',$exception);
            $notifications = $this->generateNotifications(false,"something_went_wrong");
            // Flash each message to the session
            foreach ($notifications as $notification) {
                session()->flash('messages', session('messages', collect())->push($notification));
            }
            return false;
        }
    }

    /**
     * @param $level_id
     * @return View
     */
    function userResponses($level_id): View
    {
        $level = Level::findorfail(base64_decode($level_id));
        $responses = Response::whereHas('question',function ($query) use ($level) {
            $query->where('level_id', $level->id);
        })->with('question')
            ->where('user_id', Auth::id())
            ->get();

        return view('pages.user.user_responses_list', compact('responses','level'));
    }

    function competitorsLevelOrder($level_id): View
    {
        $level = Level::findorfail(base64_decode($level_id));

        $results = $audit_finish = CompetitionsOrder::getCompetitorsOrder($level,false,false);
        $users = $results["users"];
        $audit_finish = $results["audit_finish"];
        return view('pages.user.competitors_level_order', compact('level','users','audit_finish'));
    }

    function competitorsCompetitionOrder($competitions_id): View
    {
        $competition = Competition::findorfail(base64_decode($competitions_id));

        $results = $audit_finish = CompetitionsOrder::getCompetitorsOrder($competition,false,true);
        $users = $results["users"];
        $audit_finish = $results["audit_finish"];
        return view('pages.user.competitors_competition_order', compact('competition','users','audit_finish'));
    }

    public function getCompetitionWithLevels(int $competitionId): Competition
    {
        return Competition::with('levels')->findOrFail($competitionId);
    }

    /**
     * Get unanswered questions for a level and user
     * @param int $levelId
     * @param int $userId
     * @return Collection
     */
    public function getUnansweredQuestions(int $levelId, int $userId): Collection
    {
        return Question::where('level_id', $levelId)
            ->whereDoesntHave('responses', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->get();
    }

    /**
     * Create a new response
     * @param array $data
     * @return Response
     */
    public function createResponse(array $data): Response
    {
        return Response::create($data);
    }

    /**
     * Update a response
     * @param int $question_id
     * @param array $data
     * @return bool
     */
    public function updateResponse(int $question_id, array $data): bool
    {
        try {
            $response = Response::where([
                'question_id' => $question_id,
                'user_id' => Auth::id(),
            ])->first();
            $response->update($data);
            return true;
        } catch (Exception $exception) {
            $this->registerLogs('ٌUser Response Update error: ',$exception);
            throw $exception;
        }
    }

    /**
     * Get user responses for a level
     * @param int $levelId
     * @param int $userId
     * @return Collection of responses
     */
    public function getUserLevelResponses(int $levelId, int $userId): Collection
    {
        return Response::whereHas('question', function ($query) use ($levelId) {
            $query->where('level_id', $levelId);
        })
        ->with('question')
        ->where('user_id', $userId)
        ->get();
    }
    
}
