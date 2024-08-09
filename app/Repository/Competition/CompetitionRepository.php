<?php

namespace App\Repository\Competition;

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
        $competition = Competition::with("levels")->findorfail(base64_decode($id));
        $admins = Admin::all();
        return view("pages.admin.competitions.edit.competition_edit",compact("competition","admins"));
    }

    function update(Competition $competition, array $data): void
    {
        try {
            $competition->start_date = $data['start_date'];
            $competition->age_start = $data['age_start'];
            $competition->age_end = $data['age_end'];
            $competition->save();

            $notifications = $this->generateNotifications(true,"updated");

        }catch (Exception $exception){
            $this->registerLogs('Competition updating error: ',$exception);
            $notifications = $this->generateNotifications(false,"updated");
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
            if($competition->canEdit()){
                $competition->users()->syncWithoutDetaching($user_ids);
                $notifications = $this->generateNotifications(true,"saved");
            }else{
                $notifications = $this->generateCustomNotifications(__('messages.validation.not_allow.active_competition_update'),"error");
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
