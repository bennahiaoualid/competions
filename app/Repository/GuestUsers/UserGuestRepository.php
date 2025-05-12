<?php

namespace App\Repository\GuestUsers;

use App\Http\Helpers\UsersGlobalOrder;
use App\Interface\GuestUsers\UserGuestRepositoryInterface;
use App\Models\Competition\Question;
use App\Models\GuestUsers\Choice;
use App\Models\GuestUsers\GlobalQuestion;
use App\Models\GuestUsers\GlobalResponse;
use App\Traits\CrudOperationNotificationAlert;
use App\Traits\RegisterLogs;
use Exception;
use Illuminate\Support\Facades\View;
use function PHPUnit\Framework\isNull;

class UserGuestRepository implements UserGuestRepositoryInterface
{
    use RegisterLogs , CrudOperationNotificationAlert;

    public function welcome(): \Illuminate\Contracts\View\View
    {
        return view('pages.user.guest_users.prepare');
    }

    function getRandomQuestion():\Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
    {


        $user = auth()->user();
        // get the question

        $questions = GlobalQuestion::with(['responses','choices'])
            ->whereNotNull('approved') // Now this will apply correctly
            ->get();

        $filteredQuestions = $questions->filter(function ($question) use ($user) {
            $userResponses = $question->responses->where('user_id', $user->id);
            if ($userResponses->isEmpty()) {
                return true;
            }
            return $userResponses->count() == 1 && $userResponses->first()->score == 0;
        });

        $question = $filteredQuestions->isNotEmpty() ? $filteredQuestions->random() : null;



        if (!$question) {
            return view('pages.user.guest_users.no_question');
        }

        // init the response
        if (! $this->initResponse($question->id, $user->id)){
            return redirect()->back();
        }

        // Shuffle choices in random order
        $question->choices = $question->choices->shuffle();

        // Store the start time in the session
        session(['start_time' => now()]);

        return view('pages.user.guest_users.question_response', compact('question'));
    }

    public function storeResponse(array $data): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse
    {
        try {
            $user = auth()->user();
            if (!session()->has('start_time')) {
                return redirect()->route('user.global_questions.index');
            }

            // Retrieve the start time and response ID from the session
            $startTime = session('start_time');

            // Calculate the response time

            $responseTime = $startTime->diffInUTCSeconds(now());

            // get the choice object
            $choice = Choice::findorfail($data['choice_id']);

            // get the question object
            $question = GlobalQuestion::findorfail($data['question_id']);

            if ($this->isCompatibleChoiceResponse($data['question_id'], $choice)){

                $score = $this->calcResponseScore($choice, $question, $responseTime);

                // Update the response record
                $response = GlobalResponse::where([
                    'question_id' => $data['question_id'],
                    'user_id' => auth()->id(),
                    'choice_id' => null,
                ])->orderBy('created_at','desc')->first();

                $response->update([
                    'score' => $score,
                    'response_duration' => round($responseTime,2),
                    'choice_id' => $data['choice_id'],
                ]);

                // Clear session data
                session()->forget(['start_time']);

            }

            // data to show to user
            $data_result = [
                'question' => $question,
                'choice' => $choice['choice_text'],
                'response' => $response,
                'correct' => $choice->correct,
            ];

            return view('pages.user.guest_users.response_score', compact('data_result'));

        }catch (Exception $exception){
            $this->registerLogs('ٌUser Globale Response Store error: ',$exception);
            $notifications = $this->generateNotifications(false,"something_went_wrong");
            // Flash each message to the session
            foreach ($notifications as $notification) {
                session()->flash('messages', session('messages', collect())->push($notification));
            }
            return redirect()->back();
        }
    }

    public function globalUsersOrder(): \Illuminate\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        // TODO: Implement globalUsersOrder() method.
        $users_data = UsersGlobalOrder::getUsersGlobalOrder();
        $users = $users_data["users"];
        $user_rank = $users_data["userRank"];
        $user_page = $users_data["userPage"];

        return view('pages.user.guest_users.global_order', compact('users', 'user_rank', 'user_page'));
    }

    /**
     * @param $question_id
     * @param $user_id
     * @return bool
     */
    private function initResponse($question_id, $user_id): bool
    {

        try {
            GlobalResponse::create([
                'score' => '0',
                'question_id' => $question_id,
                'user_id' => $user_id,
                'choice_id' => null,
                'response_duration' => '0'
            ]);
            return true;
        }catch (Exception $exception){
            $this->registerLogs('ٌGlobal Response creation error: ',$exception);
            $notifications = $this->generateNotifications(false,"something_went_wrong");
            // Flash each message to the session
            foreach ($notifications as $notification) {
                session()->flash('messages', session('messages', collect())->push($notification));
            }
            return false;
        }
    }

    public function getGlobalUserResponse(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Foundation\Application|false
    {
        try {
            $user = auth()->user();

            $questions = GlobalQuestion::whereHas('responses', function ($query) use ($user){
                $query->where('user_id', $user->id);
            })
                ->with(['responses' => function ($query) use ($user) {
                    $query->where('user_id', $user->id)->with('choice');
                }])
                ->paginate(10);

            return view('pages.user.guest_users.user_global_responses',compact('questions'));

        }catch (Exception $exception){
            $this->registerLogs('ٌGlobal User Responses getting error: ',$exception);
            $notifications = $this->generateNotifications(false,"something_went_wrong");
            // Flash each message to the session
            foreach ($notifications as $notification) {
                session()->flash('messages', session('messages', collect())->push($notification));
            }
            return false;
        }
    }

    /*
     *  this methode check if the choice and the question already compatible
     */
    private function isCompatibleChoiceResponse(int $question_id,Choice $choice):bool{

        if ($choice->question_id == $question_id) {
            return true;
        }else{
            return false;
        }
    }


    /**
     * @param Choice $choice
     * @param GlobalQuestion $question
     * @param int $response_time
     * @return float
     */
    private function calcResponseScore(Choice $choice, GlobalQuestion $question, int $response_time): float
    {
        $score = 0;
        if ($choice->correct) {
            if ($question->duration >= $response_time){
                $score =  $question->score - $response_time / $question->duration * $question->score / 2;
            }else{
                $score =  $question->score / 2;
            }
            $score = floatval(number_format($score, 2));
        }

        return $score;
    }


}
