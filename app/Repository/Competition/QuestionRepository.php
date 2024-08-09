<?php

namespace App\Repository\Competition;

use App\Interface\Competition\QuestionRepositoryInterface;
use App\Models\Competition\Level;
use App\Models\Competition\Question;
use App\Traits\CrudOperationNotificationAlert;
use App\Traits\RegisterLogs;
use Exception;
use Illuminate\View\View;


class QuestionRepository implements QuestionRepositoryInterface
{
    use RegisterLogs;
    use CrudOperationNotificationAlert;

    function all($level_id) : View
    {
        // TODO: return questions list of a level
        $level = Level::findorfail(base64_decode($level_id));
        $questions = Question::with("level")->where("level_id",$level->id)->get();
        return view("pages.admin.competitions.questions",compact("questions","level"));
    }

    public function create(array $data): void
    {
        $notifications[] = [];
        try {
            $level = Level::find($data["level_id"]);
            // check if this question level not activated yet
            if ($level->active == 0){
                // check if this user can store question
                if ($level->canEditQuestion()){
                    for ($i=0; $i < count($data["question_text"]); $i++) {
                        $level->questions()->create([
                            "question_text" => $data["question_text"][$i],
                            "duration" => $data["duration"][$i],
                            "level_id" => $level->id,
                            "max_score" => $data["max_score"][$i],
                        ]);
                    }
                    $notifications = $this->generateNotifications(true,"saved");
                }
                else{
                    $notifications = $this->generateCustomNotifications(__('messages.validation.not_allow.question_update'),"error");;
                }
            }
            else{
                $notifications = $this->generateCustomNotifications(__('messages.validation.not_allow.active_level_question_update'),"error");;
            }


        }catch (Exception $exception){
            $this->registerLogs('Questions creation error: ',$exception);
            $notifications = $this->generateNotifications(false,"saved");
        } finally {
            // Flash each message to the session
            foreach ($notifications as $notification) {
                session()->flash('messages', session('messages', collect())->push($notification));
            }
        }

    }

    public function update(Question $question, array $data): void
    {
        $notifications[] = [];
        try {

            $level = Level::find($question->level_id);
            // check if this question level not activated yet
            if ($level->active == 0){
                // check if this user can store question
                if ($level->canEditQuestion()){
                    $question->question_text = $data["question_text"];
                    $question->max_score = $data["max_score"];
                    $question->duration = $data["duration"];
                    $question->save();
                    $notifications = $this->generateNotifications(true,"saved");
                }
                else{
                    $notifications = $this->generateCustomNotifications(__('messages.validation.not_allow.question_update'),"error");;
                }
            }
            else{
                $notifications = $this->generateCustomNotifications(__('messages.validation.not_allow.active_level_question_update'),"error");;
            }

        }catch (Exception $exception){

            $this->registerLogs('Questions creation error: ',$exception);
            $notifications = $this->generateNotifications(false,"saved");

        } finally {

            // Flash each message to the session
            foreach ($notifications as $notification) {
                session()->flash('messages', session('messages', collect())->push($notification));
            }

        }

    }
}
