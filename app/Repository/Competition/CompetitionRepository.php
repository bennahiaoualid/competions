<?php

namespace App\Repository\Competition;

use App\Http\Helpers\AuditorSaveDelete;
use App\Http\Helpers\UserNotifyEmail;
use App\Interface\Competition\CompetitionRepositoryInterface;
use App\Models\Admin\Admin;
use App\Models\Competition\Competition;
use App\Models\User;
use App\Traits\CrudOperationNotificationAlert;
use App\Traits\RegisterLogs;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;


class CompetitionRepository implements CompetitionRepositoryInterface
{
    use RegisterLogs;
    use CrudOperationNotificationAlert;
    function all(): View
    {
        return view("pages.admin.competitions.competition_list");
    }

    public function create(array $data): void
    {
        try {

            // Start a transaction
            DB::beginTransaction();

            $data["admin_id"] = Auth::id();
            $competition = Competition::create($data);

            // Retrieve eligible users
            $competitionUsers = User::eligibleForCompetition($competition->age_start,  $competition->age_end,  $competition->id)->get();

            // Attach the eligible users to the competition
            $competition->users()->attach($competitionUsers->pluck('id')->toArray());

            $notifications = $this->generateNotifications(true,"saved");
            UserNotifyEmail::usersNewCompetition($competition);
            // Commit the transaction
            DB::commit();
        }catch (Exception $exception){

            // Rollback the transaction if something goes wrong
            DB::rollback();
            $this->registerLogs('Competition creation error: ',$exception);
            $notifications = $this->generateNotifications(false,"saved");

        } finally {

            // Flash each message to the session
            foreach ($notifications as $notification) {
                session()->flash('messages', session('messages', collect())->push($notification));
            }
        }

    }

    function edit($id) : View{
        $competition = Competition::with(["levels" => function ($q){
            $q->orderBy("start_date");
        }])->findorfail(base64_decode($id));
        $admins = Admin::all();
        return view("pages.admin.competitions.edit.competition_edit",compact("competition","admins"));
    }

    function update(Competition $competition, array $data): void
    {
        try {
            // Start a transaction
            DB::beginTransaction();
            $competition->start_date = $data['start_date'];
            $competition->age_start = $data['age_start'];
            $competition->age_end = $data['age_end'];
            // re-assign competitors if the age is changed
            if($competition->isDirty('age_start') || $competition->isDirty('age_end')){
                // Retrieve eligible users
                $competitionUsers = User::eligibleForCompetition($competition->age_start,  $competition->age_end)->get();

                // Attach the eligible users to the competition
                $competition->users()->sync($competitionUsers->pluck('id')->toArray());
            }
            $competition->save();
            UserNotifyEmail::usersUpdateCompetition($competition);

            $notifications = $this->generateNotifications(true,"updated");
            // Commit the transaction
            DB::commit();
        }catch (Exception $exception){
            // Rollback the transaction if something goes wrong
            DB::rollback();
            $this->registerLogs('Competition updating error: ',$exception);
            $notifications = $this->generateNotifications(false,"updated");
        } finally {
            // Flash each message to the session
            foreach ($notifications as $notification) {
                session()->flash('messages', session('messages', collect())->push($notification));
            }
        }
    }

    function delete(Competition $competition): void
    {
        try {
            if($competition->canEdit() || Auth::user()->hasRole('owner')){
                $competition->delete();
                $notifications = $this->generateNotifications(true,"deleted");
            }else{
                $notifications = $this->generateCustomNotifications(__('messages.validation.not_allow.competition_delete'),"error");;
            }
        }catch (Exception $exception){
            $this->registerLogs('Competition deleting error: ',$exception);
            $notifications = $this->generateNotifications(false,"deleted");
        } finally {
            // Flash each message to the session
            foreach ($notifications as $notification) {
                session()->flash('messages', session('messages', collect())->push($notification));
            }
        }
    }

    function getCompetitionUsers($competition_id) : View
    {
        // TODO: Implement getCompetitionUsers() method.
        $competition = Competition::findorfail(base64_decode($competition_id));
        return view("pages.admin.competitions.competition_users",compact("competition"));
    }

    function removeCompetitionUser($competition_id, $user_id): void
    {
        // TODO: Implement removeCompetitionUser() method.
        $notifications[] = [];
        try {

            $competition = Competition::findorfail($competition_id);
            $competition->users()->detach($user_id);
            $notifications = $this->generateNotifications(true,"deleted");

        }catch (Exception $exception){

            $this->registerLogs('Questions creation error: ',$exception);
            $notifications = $this->generateNotifications(false,"deleted");

        } finally {

            // Flash each message to the session
            foreach ($notifications as $notification) {
                session()->flash('messages', session('messages', collect())->push($notification));
            }

        }
    }

    function addCompetitionUsers($competition_id, array $user_ids): void
    {
        // TODO: Implement addCompetitionUsers() method.
        $notifications[] = [];
        try {
            $competition = Competition::findorfail($competition_id);
            $competition->users()->syncWithoutDetaching($user_ids);
            $notifications = $this->generateNotifications(true,"saved");

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

    function getCompetitionAuditors($competition_id) : View
    {
        // TODO: Implement getCompetitionUsers() method.
        $competition = Competition::findorfail(base64_decode($competition_id));
        return view("pages.admin.competitions.competition_auditors",compact("competition"));
    }

    function addCompetitionAuditors($competition_id, array $auditor_ids): void
    {
        // TODO: Implement addCompetitionUsers() method.
        $notifications[] = [];
        try {
            $competition = Competition::findorfail($competition_id);
            if($competition->canEdit()){
                $competition->auditors()->syncWithoutDetaching($auditor_ids);
                UserNotifyEmail::auditorNewCompetition($competition,$auditor_ids);
                $notifications = $this->generateNotifications(true,"saved");
            }else{
                $notifications = $this->generateCustomNotifications(__('messages.validation.not_allow.competition_update'),"error");;
            }

        }catch (Exception $exception){

            $this->registerLogs('auditors creation error: ',$exception);
            $notifications = $this->generateNotifications(false,"saved");

        } finally {

            // Flash each message to the session
            foreach ($notifications as $notification) {
                session()->flash('messages', session('messages', collect())->push($notification));
            }

        }
    }

    function removeCompetitionAuditor($competition_id, $auditor_id): void
    {
        // TODO: Implement removeCompetitionAuditor() method.
        $notifications[] = [];
        try {
            $competition = Competition::findorfail($competition_id);
            if($competition->canEdit()){
                // check if competition have more the 1 auditor
                if($competition->auditors->count() > 1){
                    $prepare_deleting = AuditorSaveDelete::deleteAuditor($auditor_id, $competition);
                    if ($prepare_deleting){
                        $competition->auditors()->detach($auditor_id);
                        $notifications = $this->generateNotifications(true,"deleted");
                    }
                }
               else{
                   $notifications = $this->generateCustomNotifications(__('messages.validation.not_allow.remove_auditor_only_one'),"error");;

               }
            }else{
                $notifications = $this->generateCustomNotifications(__('messages.validation.not_allow.competition_update'),"error");;
            }

        }catch (Exception $exception){

            $this->registerLogs('Auditor deleting error: ',$exception);
            $notifications = $this->generateNotifications(false,"deleted");

        } finally {

            // Flash each message to the session
            foreach ($notifications as $notification) {
                session()->flash('messages', session('messages', collect())->push($notification));
            }

        }
    }

    function activateCompetition($competition_id): void
    {
        $notifications[] = [];
        try{
            $competition = Competition::findorfail($competition_id);
            // check if competition start date is already passed
            if ($competition->start_date->lessThan(now())){

                // check if competition levels number match
                if (Competition::competitionMaxLevelNumbers($competition_id)){

                    // check if competition has at least 3 competitors
                    if ($competition->users->count() > 2){

                        // check if competition has at least 1 auditor
                        if ($competition->auditors->count() > 0){
                            // check if competition levels are still after the current time
                            if($competition->isAllLevelAfterNow()){
                                $competition->start_date = now();
                                $competition->status = "1";
                                $competition->save();
                                UserNotifyEmail::usersActivateCompetition($competition);
                                $notifications = $this->generateNotifications(true,"activated");
                            }else{
                                $notifications = $this->generateCustomNotifications(__('messages.validation.not_allow.competition_activate_level_pass'),"error");;
                            }
                        }else{
                            $notifications = $this->generateCustomNotifications(__('messages.validation.not_allow.competition_activate_less_auditor'),"error");;
                        }
                    }else{
                        $notifications = $this->generateCustomNotifications(__('messages.validation.not_allow.competition_activate_less_competitors'),"error");;
                    }

                }else{
                    $notifications = $this->generateCustomNotifications(__('messages.validation.not_allow.competition_activate_match_levels'),"error");;
                }

            }else{
                $notifications = $this->generateCustomNotifications(__('messages.validation.not_allow.competition_activate_early'),"error");;
            }
        }catch (Exception $exception){

            $this->registerLogs('competition activation error: ',$exception);
            $notifications = $this->generateNotifications(false,"activated");

        } finally {

            // Flash each message to the session
            foreach ($notifications as $notification) {
                session()->flash('messages', session('messages', collect())->push($notification));
            }

        }
    }
}
