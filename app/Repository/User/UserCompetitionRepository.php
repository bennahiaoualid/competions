<?php

namespace App\Repository\User;

use App\Http\Helpers\CompetitionsOrder;
use App\Interface\User\UserCompetitionRepositoryInterface;
use App\Models\Competition\Competition;
use App\Models\Competition\Level;
use App\Models\Competition\Question;
use App\Models\Competition\Response;
use App\Traits\CrudOperationNotificationAlert;
use App\Traits\RegisterLogs;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserCompetitionRepository implements UserCompetitionRepositoryInterface
{
    use RegisterLogs , CrudOperationNotificationAlert;
    function all(array $data,$user): View
    {
        // TODO: Implement all() method.
        $auth_user = Auth::user();
        if (count($data) < 1) {
            if ($user && Auth::check()){
                $competitions = $auth_user->competitions()->orderBy('start_date', 'desc')->get();

            }else{
                $competitions = Competition::orderBy('start_date', 'desc')->get();
            }
        }else{
            if ($user && Auth::check()){
                $competitions = $auth_user->competitions()
                    ->title($data['title'])
                    ->startDate($data['start_date_from'], $data['start_date_to'])
                    ->ageRange($data['age_start'], $data['age_end'])
                    ->status($data['status'])
                    ->orderBy('start_date', 'desc')
                    ->get();
            }else{
                $competitions = Competition::query()
                    ->title($data['title'])
                    ->startDate($data['start_date_from'], $data['start_date_to'])
                    ->ageRange($data['age_start'], $data['age_end'])
                    ->status($data['status'])
                    ->orderBy('start_date', 'desc')
                    ->get();
            }
        }
        // Return the view with competitions data
        return view('pages.user.competitions', compact('competitions'));
    }

    function userCompetition($competition_id)
    {
        // TODO: Implement userCompetition() method.
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

        $user = auth()->user();
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
            $user = auth()->user();

            // Retrieve the start time and response ID from the session
            $startTime = session('start_time');

            // Calculate the response time
            $responseTime = $startTime->diffInUTCSeconds(now());

            // Update the response record
            $response = Response::where([
                'question_id' => $data['question_id'],
                'user_id' => auth()->id(),
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

}
