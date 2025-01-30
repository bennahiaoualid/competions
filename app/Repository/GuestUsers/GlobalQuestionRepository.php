<?php

namespace App\Repository\GuestUsers;

use App\Interface\GuestUsers\GlobalQuestionRepositoryInterface;
use App\Models\Competition\Level;
use App\Models\Competition\Question;
use App\Models\GuestUsers\GlobalQuestion;
use App\Traits\CrudOperationNotificationAlert;
use App\Traits\RegisterLogs;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;


class GlobalQuestionRepository implements GlobalQuestionRepositoryInterface
{
    use RegisterLogs;
    use CrudOperationNotificationAlert;

    function all() : View
    {
        // TODO: return questions list of a level
        return view("pages.admin.guest_users.global_question_list");
    }

    public function create(array $data): void
    {
        $notifications[] = [];
        try {
            DB::beginTransaction();
            if (array_key_exists('choice',$data) && 1 < count($data['choice'])  && count($data['choice']) < 6) {
               $user = Auth::user();
               $approved = $user->hasRole(['super_admin','owner'], 'admin') ? $user->id : null;
                $question = GlobalQuestion::create([
                    'question_text' => $data['question_text'],
                    'score' => $data['score'],
                    'duration' => $data['duration'],
                    'admin_id' => $user->id,
                    'approved' => $approved,
                ]);

                for ($i=0; $i < count($data["choice"]); $i++) {
                    $question->choices()->create([
                        "choice_text" => $data["choice"][$i],
                        "correct" => $i == 0,
                    ]);
                }
                $notifications = $this->generateNotifications(true,"saved");

            }
            else{
                $notifications = $this->generateCustomNotifications(__('messages.validation.not_allow.global_question_choices'),"error");;
            }
            DB::commit();
        }catch (Exception $exception){
            DB::rollBack();
            $this->registerLogs('Global Questions creation error: ',$exception);
            $notifications = $this->generateNotifications(false,"saved");
        } finally {
            // Flash each message to the session
            foreach ($notifications as $notification) {
                session()->flash('messages', session('messages', collect())->push($notification));
            }
        }

    }

    function approve(GlobalQuestion $question)
    {
        // TODO: Implement approve() method.
        try{
            $user = Auth::user();
            $can_approve = $user->hasRole(['super_admin','owner'], 'admin');
            if($can_approve && $question->approved == null){
                $question->approved = $user->id;
                $question->save();
                $notifications = $this->generateNotifications(true,"approved");

            }
        }catch (Exception $exception){
            $this->registerLogs('Global Questions approve error: ',$exception);
            $notifications = $this->generateNotifications(false,"approved");
        } finally {
            // Flash each message to the session
            foreach ($notifications as $notification) {
                session()->flash('messages', session('messages', collect())->push($notification));
            }
        }
    }

    function delete(GlobalQuestion $question)
    {
        // TODO: Implement approve() method.
        try{
            if($question->canDelete()){
                $question->delete();
                $notifications = $this->generateNotifications(true,"deleted");
            }
        }catch (Exception $exception){
            $this->registerLogs('Global Questions delete error: ',$exception);
            $notifications = $this->generateNotifications(false,"deleted");
        } finally {
            // Flash each message to the session
            foreach ($notifications as $notification) {
                session()->flash('messages', session('messages', collect())->push($notification));
            }
        }
    }
}
