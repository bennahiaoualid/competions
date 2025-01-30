<?php

namespace App\Repository\Competition;

use App\Http\Helpers\UserNotifyEmail;
use App\Interface\Competition\LevelRepositoryInterface;
use App\Models\Admin\Admin;
use App\Models\Competition\Competition;
use App\Models\Competition\Level;
use App\Models\Competition\Question;
use App\Models\Competition\Response;
use App\Traits\CrudOperationNotificationAlert;
use App\Traits\RegisterLogs;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;


class LevelRepository implements LevelRepositoryInterface
{
    use RegisterLogs;
    use CrudOperationNotificationAlert;

    public function create(array $data): void
    {
        try {
            if(Competition::competitionMaxLevelNumbers($data["competition_id"])){
                $notifications = $this->generateCustomNotifications(__('messages.validation.not_allow.competition_max_levels'),"error");;
            }
            else{
                if(Competition::find($data["competition_id"])->start_date->gt(Carbon::parse($data["start_date"]))){
                    $notifications = $this->generateCustomNotifications(__('validation.custom.start_date_gt_competition'),"error");;
                }
                else{
                    $time_confile = Level::hasTimeConflict($data["competition_id"],$data["start_date"],$data["duration"]);
                    if ($time_confile) {
                        $notifications = $this->generateCustomNotifications(__('messages.validation.not_allow.level_time_conflict'),"error");;
                    } else{
                        $level = Level::create($data);
                        UserNotifyEmail::adminLevel($level);
                        $notifications = $this->generateNotifications(true,"saved");
                    }
                }
            }
        }catch (Exception $exception){
            $this->registerLogs('Level creation error: ',$exception);
            $notifications = $this->generateNotifications(false,"saved");
        } finally {
            // Flash each message to the session
            foreach ($notifications as $notification) {
                session()->flash('messages', session('messages', collect())->push($notification));
            }
        }

    }

    function edit($id) : View{
        $level = Level::findorfail(base64_decode($id));
        $admins = Admin::all();
        return view("pages.admin.competitions.edit.level_edit",compact("level","admins"));
    }

    function update(Level $level, array $data): void
    {
        try {
            $notify_user = false;
            $conflict = false;
            if ($level->status == 0){
                $level->name = $data['name'];
                $level->description = $data['description'];
                $level->start_date = $data['start_date'];
                $level->duration = $data['duration'];
                $level->admin_id = $data['admin_id'];
                if ($level->isDirty('start_date')){
                    $notify_user = true;
                    $conflict = Level::hasTimeConflict($level->competition_id, $data['start_date'], $level->duration , $level->id);
                }
                if (!$conflict){
                    $level->save();
                    if ($notify_user){
                        UserNotifyEmail::usersUpdateLevel($level->competition,$level);
                    }
                    $notifications = $this->generateNotifications(true,"updated");
                }else{
                    $notifications = $this->generateCustomNotifications(__('messages.validation.not_allow.level_activate_time_conflict'),"error");;
                }

            }
            else{
                $notifications = $this->generateCustomNotifications(__('messages.validation.not_allow.active_level_update'),"error");;
            }
        }catch (Exception $exception){
            $this->registerLogs('Level updating error: ',$exception);
            $notifications = $this->generateNotifications(false,"updated");
        } finally {
            // Flash each message to the session
            foreach ($notifications as $notification) {
                session()->flash('messages', session('messages', collect())->push($notification));
            }
        }
    }

    function delete(Level $level): void
    {
        try {
            if($level->competition->canEdit()){
                if($level->competition->status == 0){
                    $level->delete();
                    $notifications = $this->generateNotifications(true,"deleted");
                }else{
                    $notifications = $this->generateCustomNotifications(__('messages.validation.not_allow.active_competition_update'),"error");
                }
            }else{
                $notifications = $this->generateCustomNotifications(__('messages.validation.not_allow.competition_update'),"error");
            }
        }catch (Exception $exception){
            $this->registerLogs('Level deleting error: ',$exception);
            $notifications = $this->generateNotifications(false,"deleted");
        } finally {
            // Flash each message to the session
            foreach ($notifications as $notification) {
                session()->flash('messages', session('messages', collect())->push($notification));
            }
        }
    }

    function activateLevel($level_id): void
    {
        $notifications[] = [];
        try{
            $level = Level::findorfail($level_id);
            // check if competition already activated
            if ($level->competition->status == 1){

                // check if level start time already passed
                if ($level->start_date->lessThan(now())){

                    // check questions number match the level info
                    if ($level->questions->count() == $level->questions_number){

                        // check if the previous level finished
                        if ($level->isTheEarliest()){

                            // check if the previous level responses audit it
                            if ($level->isThePreviousAudit()){

                                // check if competition levels are still after the current time
                                if($level->competition->isAllLevelAfterNow($level->id)){

                                // check that if this level get now as start_date will not conflict with other levels
                                    $new_start_date = now();
                                    if (!Level::hasTimeConflict($level->competition_id, $new_start_date, $level->duration , $level->id)){
                                        if ($level->canEDit()){
                                            $level->start_date = now();
                                            $level->status = "1";
                                            $level->save();
                                            UserNotifyEmail::usersActivateLevel($level->competition,$level);
                                            $notifications = $this->generateNotifications(true,"activated");
                                        }
                                    }else{
                                        $notifications = $this->generateCustomNotifications(__('messages.validation.not_allow.level_activate_time_conflict'),"error");;
                                    }

                                }else{
                                    $notifications = $this->generateCustomNotifications(__('messages.validation.not_allow.competition_activate_level_pass'),"error");;
                                }

                            }else{
                                $notifications = $this->generateCustomNotifications(__('messages.validation.not_allow.level_activate_previous_not_audit'),"error");;
                            }

                        }else{
                            $notifications = $this->generateCustomNotifications(__('messages.validation.not_allow.level_activate_not_its_tour'),"error");;
                        }

                    }else{
                        $notifications = $this->generateCustomNotifications(__('messages.validation.not_allow.level_activate_match_questions'),"error");;
                    }

                }else{
                    $notifications = $this->generateCustomNotifications(__('messages.validation.not_allow.level_activate_early'),"error");;
                }

            }else{
                $notifications = $this->generateCustomNotifications(__('messages.validation.not_allow.level_activate_before_competition'),"error");;
            }
        }catch (Exception $exception){

            $this->registerLogs('activated level error: ',$exception);
            $notifications = $this->generateNotifications(false,"activated");

        } finally {

            // Flash each message to the session
            foreach ($notifications as $notification) {
                session()->flash('messages', session('messages', collect())->push($notification));
            }

        }
    }

    function finishLevel($level_id): void
    {
        $notifications[] = [];
        try{
            $level = Level::findorfail($level_id);
            // check if the level already passed
            if ($level->status == 1 && !$level->isStillActive()){
                if ($level->canEDit()){
                    $this->fillEmptyQuestionResponse($level);
                    $this->assignUsersToAuditors($level);
                    $level->status = "2";
                    $level->save();
                    UserNotifyEmail::auditorsFinishLevel($level->competition,$level);
                    $notifications = $this->generateNotifications(true,"finish");
                }

            }else{
                $notifications = $this->generateCustomNotifications(__('messages.validation.not_allow.level_finish_still_active'),"error");;
            }
        }catch (Exception $exception){

            $this->registerLogs('finish level error: ',$exception);
            $notifications = $this->generateNotifications(false,"finish");

        } finally {

            // Flash each message to the session
            foreach ($notifications as $notification) {
                session()->flash('messages', session('messages', collect())->push($notification));
            }

        }
    }

    /**
     * methode fill the questions that the users didnt answer with empty responses before finish the level .
     * @param $level
     * @return void
     * @throws Exception
     */
    private function fillEmptyQuestionResponse($level): void
    {
        $users = $level->competition->users;
        try{
            DB::beginTransaction();
            foreach ($users as $user) {
                // get an answered questions
                $questions = Question::where('level_id', $level->id)
                    ->whereDoesntHave('responses', function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    })->get();

                // create empty responses
                foreach ($questions as $question) {
                    Response::create([
                        'response_text' => '',
                        'question_id' => $question->id,
                        'user_id' => $user->id,
                        'admin_id' => null,
                    ]);
                }
            }
            DB::commit();
        }catch (Exception $exception){
            DB::rollBack();
            $this->registerLogs('filling empty question error: ',$exception);
            throw $exception;
        }

    }

    /**
     * methode assign the users answer to the competition audtiors .
     * @param $level
     * @return void
     * @throws Exception
     */
    private function assignUsersToAuditors($level): void
    {
        try{
            // Step 1: Retrieve users and auditors
            $users = $level->competition->users;
            $auditors = $level->competition->auditors;

            if ($users->isEmpty() || $auditors->isEmpty()) {
                return; // No users or auditors to assign
            }

            // Step 2: Shuffle users randomly
            $shuffledUsers = $users->shuffle();

            // Step 3: Divide users equally among auditors
            $assignments = [];
            $auditorCount = $auditors->count();
            $index = 0;

            foreach ($shuffledUsers as $user) {
                $auditor = $auditors[$index % $auditorCount];
                $assignments[] = [
                    'level_id' => $level->id,
                    'user_id' => $user->id,
                    'admin_id' => $auditor->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $index++;
            }

            // Step 4: Insert all assignments at once
            DB::table('level_admin_user')->insert($assignments);

        }catch (Exception $exception){
            $this->registerLogs('assign users responses to auditors error: ',$exception);
            throw $exception;
        }

    }
}
