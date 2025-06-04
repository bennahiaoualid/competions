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
use Illuminate\Database\Eloquent\Collection;


class CompetitionRepository implements CompetitionRepositoryInterface
{
    use RegisterLogs;
    use CrudOperationNotificationAlert;


    public function findById(int|string $id, bool $withLevels = false): ?Competition
    {
        $query = Competition::query();
        if ($withLevels) {
            $query->with(["levels" => function ($q){
                $q->orderBy("start_date");
            }]);
        }
        return $query->find($id);
    }
    
    public function findOrFail(int|string $id, bool $withLevels = false): Competition
    {
        $query = Competition::query();
        if ($withLevels) {
            $query->with(["levels" => function ($q){
                $q->orderBy("start_date");
            }]);
        }
        return $query->findOrFail($id);
    }

    public function create(array $data): Competition
    {
        $data["admin_id"] = Auth::id();
        return  Competition::create($data);
    }

    /**
     * Update a competition and return an array with the resyncCompetitionParticipants flag and the competition object.
     *
     * @param Competition $competition The competition to update.
     * @param array $data The data to update the competition with.
     * @return array An array containing the resyncCompetitionParticipants flag and the updated competition object.
    **/
    public function update(Competition $competition, array $data): array
    {
        $competition->fill($data);
        $resyncCompetitionParticipants = false;

        if($competition->isDirty('age_start') || $competition->isDirty('age_end')){
            $resyncCompetitionParticipants = true;
        }
        $competition->save();
        return [
            'resyncCompetitionParticipants' => $resyncCompetitionParticipants,
            'competition' => $competition
            ];
    }

    public function delete(Competition $competition): bool
    {
        $result = $competition->delete();
        return $result;
    }

    /**
     * Add users to a competition.
     *
     * @param Competition $competition The competition to add users to.
     * @param array $user_ids The IDs of the users to add.
     * @return bool True if the users were added successfully, false otherwise.
     */
    public function addUsersToCompetition(Competition $competition, array $user_ids): bool
    {
        // 1. Filter out empty/non-numeric IDs and get unique input IDs
        $unique_input_user_ids = array_values(array_unique(array_filter($user_ids, 'is_numeric')));

        if (empty($unique_input_user_ids)) {
            return true; // No valid IDs to process
        }

        // 2. Get IDs of users already attached to the competition
        $existing_user_ids = $competition->users()->allRelatedIds()->toArray();

        // 3. Determine which IDs are genuinely new
        $new_user_ids = array_diff($unique_input_user_ids, $existing_user_ids);

        // 4. If there are new users to add, attach them.
        if (!empty($new_user_ids)) {
            $competition->users()->attach($new_user_ids);
        }

        return true;
    }

    /**
     * Remove a user from a competition.
     *
     * @param Competition $competition The competition to remove the user from.
     * @param int $user_id The ID of the user to remove.
     * @return bool True if the user was removed successfully, false otherwise.
     */   
    public function removeUserFromCompetition(Competition $competition, int $user_id): bool
    {
        try {
            $competition->users()->detach($user_id);
            return true;
        } catch (Exception $e) {
            $this->registerLogs('Error removing user from competition: ', $e);
            return false;
        }
    }

    public function addAuditorsToCompetition(Competition $competition, array $auditor_ids): bool
    {
        // 1. Filter out empty/non-numeric IDs and get unique input IDs
        $unique_input_auditor_ids = array_values(array_unique(array_filter($auditor_ids, 'is_numeric')));

        if (empty($unique_input_auditor_ids)) {
            return true; // No valid IDs to process
        }

        // 2. Get IDs of auditors already attached to the competition
        $existing_auditor_ids = $competition->auditors()->allRelatedIds()->toArray();

        // 3. Determine which IDs are genuinely new
        $new_auditor_ids = array_diff($unique_input_auditor_ids, $existing_auditor_ids);

        // 4. If there are new auditors to add, attach them.
        // This will perform a bulk insert if the pivot model ('admin_competition')
        // does not have timestamps or event listeners forcing individual inserts.
        if (!empty($new_auditor_ids)) {
            $competition->auditors()->attach($new_auditor_ids);
        }

        return true;
    }

    public function removeAuditorFromCompetition(Competition $competition, int $auditor_id): bool
    {
        $competition->auditors()->detach($auditor_id);
        return true;
    }

    public function activate(Competition $competition): bool
    {
        $competition->status = "1";
        $result = $competition->save();
        return $result;
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

    function find($id)
    {
        return Competition::findOrFail($id);
    }

    function hasValidLevelNumbers($competition_id): bool
    {
        return Competition::competitionMaxLevelNumbers($competition_id);
    }

    function hasEnoughCompetitors($competition_id): bool
    {
        $competition = $this->find($competition_id);
        return $competition->users->count() > 2;
    }

    function hasAuditors($competition_id): bool
    {
        $competition = $this->find($competition_id);
        return $competition->auditors->count() > 0;
    }

    function areAllLevelsAfterNow($competition_id): bool
    {
        $competition = $this->find($competition_id);
        return $competition->isAllLevelAfterNow();
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
