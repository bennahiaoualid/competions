<?php

namespace App\Services\Competition;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Competition\Competition;
use App\Traits\RegisterLogs; // For logging errors
use App\Jobs\Competetion\SyncCompetitionParticipants;
use App\Http\Helpers\UserNotifyEmail; // For sending emails
use App\Interface\Competition\CompetitionRepositoryInterface;
use App\Traits\CrudOperationNotificationAlert; // For notifications
use App\Http\Helpers\AuditorSaveDelete; // For auditor specific logic
use App\Models\User; // For Auth::user() type hinting if specific methods are used

class CompetitionService
{
    use CrudOperationNotificationAlert, RegisterLogs;

    public function __construct(
        protected CompetitionRepositoryInterface $competitionRepository
    ) {
    }

    public function findCompetitionById(string $id) // ID is base64 encoded as per original edit view
    {
        $decodedId = base64_decode($id);
        return $this->competitionRepository->findById($decodedId);
    }

    public function createCompetition(array $data): bool
    {
        DB::beginTransaction();
        try {
            $competition = $this->competitionRepository->create($data);

            // Sync the participants of the competition
            SyncCompetitionParticipants::dispatch($competition,isUpdate: false)->afterCommit();
            
            DB::commit();
            session()->flash(
                'messages',
                collect(session('messages', []))->merge($this->generateNotifications(true, "saved"))
            );
            return true;
        } catch (Exception $exception) {
            DB::rollback();
            $this->registerLogs('Competition creation error: ', $exception);
            session()->flash(
                'messages',
                collect(session('messages', []))->merge($this->generateNotifications(false, "saved"))
            );
            return false;
        }
    }

    public function updateCompetition(Competition $competition, array $data): bool
    {
        if (!$competition) {
            session()->flash(
                'messages',
                collect(session('messages', []))->merge($this->generateCustomNotifications('Competition not found.', "error"))
            );
            return false;
        }

        DB::beginTransaction();
        try {
            $result = $this->competitionRepository->update($competition, $data);
            $competition  = $result['competition'];
            $resync = $result['resyncCompetitionParticipants'];
             // Sync the participants of the competition
            if($resync){
                SyncCompetitionParticipants::dispatch($competition,isUpdate: true)->afterCommit();
            }else{
                UserNotifyEmail::usersUpdateCompetition($competition); // Notification
            }
            DB::commit();
            session()->flash(
                'messages',
                collect(session('messages', []))->merge($this->generateNotifications(true, 'updated'))
            );
            return true;
        } catch (Exception $exception) {
            DB::rollback();
            $this->registerLogs('Competition updating error: ', $exception);
            session()->flash(
                'messages',
                collect(session('messages', []))->merge($this->generateNotifications(false, "updated"))
            );
            return false;
        }
    }

    public function deleteCompetition(string $competitionId): bool
    {
        $competition = $this->competitionRepository->findById($competitionId); // Assuming ID is not base64 here
        if (!$competition) {
            session()->flash(
                'messages',
                collect(session('messages', []))->merge($this->generateCustomNotifications('Competition not found.', "error"))
            );
            return false;
        }

        // Authorization check (moved from repository)
        // Assuming User model has a hasRole method or similar, or use Gates/Policies
        if (!($competition->canEdit() || (Auth::user() instanceof User && Auth::user()->hasRole('owner')))) {
            session()->flash(
                'messages',
                collect(session('messages', []))->merge($this->generateCustomNotifications(__('messages.validation.not_allow.competition_delete'),"error"))
            );
            return false;
        }

        DB::beginTransaction();
        try {
            $this->competitionRepository->delete($competition);
            DB::commit();
            session()->flash(
                'messages',
                collect(session('messages', []))->merge($this->generateNotifications(true, "deleted"))
            );
            return true;
        } catch (Exception $exception) {
            DB::rollback();
            $this->registerLogs('Competition deleting error: ', $exception);
            session()->flash(
                'messages',
                collect(session('messages', []))->merge($this->generateNotifications(false, "deleted"))
            );
            return false;
        }
    }

    public function addCompetitionUsers(Competition $competition, array $user_ids): bool
    {
        try {
            $this->competitionRepository->addUsersToCompetition($competition, $user_ids);
            // UserNotifyEmail::usersAddedToCompetition($competition, $user_ids); // Example notification
            session()->flash(
                'messages',
                collect(session('messages', []))->merge($this->generateNotifications(true, "saved"))
            );
            return true;
        } catch (Exception $exception) {
            $this->registerLogs('Error adding users to competition: ', $exception);
            session()->flash(
                'messages',
                collect(session('messages', []))->merge($this->generateNotifications(false, "saved"))
            );
            return false;
        }
    }
    
    public function removeCompetitionUser(int $competition_id, int $user_id): bool
    {
        $competition = $this->competitionRepository->findById($competition_id);
        if (!$competition) return false; // Or throw exception

        try {
            $this->competitionRepository->removeUserFromCompetition($competition, $user_id);
            session()->flash(
                'messages',
                collect(session('messages', []))->merge($this->generateNotifications(true, "deleted"))
            );
            return true;
        } catch (Exception $exception) {
            $this->registerLogs('Error removing user from competition: ', $exception);
            session()->flash(
                'messages',
                collect(session('messages', []))->merge($this->generateNotifications(false, "deleted"))
            );
            return false;
        }
    }

    /**
     * Handles the adding of a auditors to a competition request and returns a response with notifications.
     *
     * @param Competition $competition The competition object.
     * @param array $auditor_ids The IDs of the auditors to add.
     * @return bool True if the auditors were added successfully, false otherwise.
     */
    public function addCompetitionAuditors(Competition $competition, array $auditor_ids): bool
    {

        if (!$competition->canEdit()) {
            session()->flash(
                'messages',
                collect(session('messages', []))->merge($this->generateCustomNotifications(__('messages.validation.not_allow.competition_update'),"error"))
            );
            return false;
        }

        try {
            $this->competitionRepository->addAuditorsToCompetition($competition, $auditor_ids);
            UserNotifyEmail::auditorNewCompetition($competition, $auditor_ids); // Notification
            session()->flash(
                'messages',
                collect(session('messages', []))->merge($this->generateNotifications(true, "saved"))
            );
            return true;
        } catch (Exception $exception) {
            $this->registerLogs('Error adding auditors to competition: ', $exception);
            session()->flash(
                'messages',
                collect(session('messages', []))->merge($this->generateNotifications(false, "saved"))
            );
            return false;
        }
    }

    public function removeCompetitionAuditor(int $competition_id, int $auditor_id): bool
    {
        $competition = $this->competitionRepository->findById($competition_id);
        if (!$competition) return false;

        if (!$competition->canEdit()) {
            session()->flash(
                'messages',
                collect(session('messages', []))->merge($this->generateCustomNotifications(__('messages.validation.not_allow.competition_update'),"error"))
            );
            return false;
        }
        if($competition->auditors->count() <= 1){
            session()->flash(
                'messages',
                collect(session('messages', []))->merge($this->generateCustomNotifications(__('messages.validation.not_allow.remove_auditor_only_one'),"error"))
            );
            return false;
        }

        try {
            // AuditorSaveDelete logic - if it's complex, it might be its own service or helper
            // For now, assuming it does some checks before actual deletion
            if (!AuditorSaveDelete::deleteAuditor($auditor_id, $competition)) { // Assuming this returns bool
                // Notification for this specific failure can be added if AuditorSaveDelete sets it or returns specific error
                session()->flash(
                    'messages',
                    collect(session('messages', []))->merge($this->generateCustomNotifications('Failed pre-delete check for auditor.',"error"))
                );
                return false;
            }

            $this->competitionRepository->removeAuditorFromCompetition($competition, $auditor_id);
            session()->flash(
                'messages',
                collect(session('messages', []))->merge($this->generateNotifications(true, "deleted"))
            );
            return true;
        } catch (Exception $exception) {
            $this->registerLogs('Error removing auditor from competition: ', $exception);
            session()->flash(
                'messages',
                collect(session('messages', []))->merge($this->generateNotifications(false, "deleted"))
            );
            return false;
        }
    }

    public function activateCompetition(Competition $competition): bool
    {
        // All business logic for activation, moved from repository
        if ($competition->start_date->greaterThanOrEqualTo(now())){
            session()->flash(
                'messages',
                collect(session('messages', []))->merge($this->generateCustomNotifications(__('messages.validation.not_allow.competition_activate_early'),"error"))
            );
            return false;
        }
        if ($competition->levels->count() != $competition->levels_number){ // Assuming this is a static model method or needs context
            session()->flash(
                'messages',
                collect(session('messages', []))->merge($this->generateCustomNotifications(__('messages.validation.not_allow.competition_activate_match_levels'),"error"))
            );
            return false;
        }
        if ($competition->users->count() <= 2){
            session()->flash(
                'messages',
                collect(session('messages', []))->merge($this->generateCustomNotifications(__('messages.validation.not_allow.competition_activate_less_competitors'),"error"))
            );
            return false;
        }
        if ($competition->auditors->count() == 0){
            session()->flash(
                'messages',
                collect(session('messages', []))->merge($this->generateCustomNotifications(__('messages.validation.not_allow.competition_activate_less_auditor'),"error"))
            );
            return false;
        }
        if(!$competition->isAllLevelAfterNow()){ // Model method
            session()->flash(
                'messages',
                collect(session('messages', []))->merge($this->generateCustomNotifications(__('messages.validation.not_allow.competition_activate_level_pass'),"error"))
            );
            return false;
        }

        DB::beginTransaction();
        try {
            $competition->start_date = now(); // Part of activation logic
            // The repository's activate method now only sets status and saves.
            $this->competitionRepository->activate($competition);
            UserNotifyEmail::usersActivateCompetition($competition);
            DB::commit();
            session()->flash(
                'messages',
                collect(session('messages', []))->merge($this->generateNotifications(true, "activated"))
            );
            return true;
        } catch (Exception $exception) {
            DB::rollback();
            $this->registerLogs('Competition activation error: ', $exception);
            session()->flash(
                'messages',
                collect(session('messages', []))->merge($this->generateNotifications(false, "activated"))
            );
            return false;
        }
    }
    
}
