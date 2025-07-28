<?php

namespace App\Services\Competition;

use Exception;
use App\Models\Admin\Admin;
use App\Contracts\FlasherInterface;
use Illuminate\Support\Facades\Auth;
use App\Models\Competition\Competition;
use App\Contracts\TransactionManagerInterface;
use App\Jobs\Competition\SafeDeleteAuditorJob;
use App\Services\Monitoring\JobTrackingService;
use App\Traits\RegisterLogs; // For logging errors
use App\Jobs\Competetion\SyncCompetitionParticipants;
use App\Helpers\UserNotifyEmail; // For sending emails
use App\Interface\Competition\CompetitionRepositoryInterface;
use App\Services\Notification\OptimizedCompetitionNotificationService;
use App\Traits\CrudOperationNotificationAlert; // For notifications
use App\Models\User; // For Auth::user() type hinting if specific methods are used

class CompetitionService
{
    use CrudOperationNotificationAlert; 
    use RegisterLogs;

    /**
     * Constructor for the CompetitionService.
     *
     * @param CompetitionRepositoryInterface $competitionRepository The repository for competition operations.
     */
    public function __construct(
        protected CompetitionRepositoryInterface $competitionRepository,
        protected TransactionManagerInterface $transactionManager,
        protected FlasherInterface $flasher,
        protected JobTrackingService $jobTrackingService,
        protected OptimizedCompetitionNotificationService $notificationService
    ) {
    }

    /**
     * Find a competition by ID.
     *
     * @param string|int $id_b64 The ID of the competition.
     * @param bool $base64 Whether the ID is base64 encoded.
     * @return Competition|null The competition object if found, null otherwise.
     */
    public function findCompetitionById(string|int $id_b64, bool $base64 = true) // ID is base64 encoded as per original edit view
    {
        if ($base64) {
            $decodedId = base64_decode($id_b64);
        } else {
            $decodedId = $id_b64;
        }
        return $this->competitionRepository->findById($decodedId);
    }

    /**
     * Create a competition.
     *
     * @param array $data The data to create the competition with.
     * @return bool True if the competition was created successfully, false otherwise.
     */
    public function createCompetition(array $data): bool
    {
        try {
            $result = $this->transactionManager->run(function () use ($data) {
                $competition = Competition::create(array_merge($data, ['admin_id' => Auth::id()]));
                SyncCompetitionParticipants::dispatch($competition)->afterCommit();
                // Send notification to eligible users
                $this->notificationService->competitionCreated($competition);
                
                return true;
            });
            $this->flasher->crudSuccess('saved');
            return $result;
        } catch (Exception $exception) {
            $this->registerLogs('Competition creation error: ', $exception);
            $this->flasher->crudFailure('saved');
            return false;
        }
    }

    /**
     * Update a competition.
     *
     * @param Competition $competition The competition object.
     * @param array $data The data to update the competition with.
     * @return bool True if the competition was updated successfully, false otherwise.
     */
    public function updateCompetition(Competition $competition, array $data): bool
    {
        try {
            $result = $this->transactionManager->run(function () use ($competition, $data) {
                $result = $this->competitionRepository->update($competition, $data);
                $competition = $result['competition'];
                $resync = $result['resyncCompetitionParticipants'];
                
                if($resync){
                    SyncCompetitionParticipants::dispatch($competition, isUpdate: true)->afterCommit();
                } else {
                    UserNotifyEmail::usersUpdateCompetition($competition);
                }
                // Send notification to competition users
                $this->notificationService->competitionUpdated($competition);
                return true;
            });

            $this->flasher->crudSuccess('updated');
            return $result;
        } catch (Exception $exception) {
            $this->registerLogs('Competition updating error: ', $exception);
            $this->flasher->crudFailure('updated');
            return false;
        }
    }

    /**
     * Delete a competition.
     *
     * @param int $competitionId The ID of the competition to delete.
     * @return bool True if the competition was deleted successfully, false otherwise.
     */
    public function deleteCompetition(int $competitionId): bool
    {
        try {
            $competition = $this->competitionRepository->findById($competitionId);
            if (!$competition) {
                $this->flasher->error(__('messages.validation.404.competition'));
                return false;
            }

            if (!($competition->canEdit())) {
                $this->flasher->error(__('messages.validation.not_allow.competition_delete'));
                return false;
            }

            $competition->delete();
            $this->flasher->crudSuccess('deleted');
            return true;

        } catch (Exception $exception) {
            $this->registerLogs('Competition deleting error: ', $exception);
            $this->flasher->crudFailure('deleted');
            return false;
        }
    }

    /**
     * Add users to a competition.
     *
     * @param Competition $competition The competition object.
     * @param array $user_ids The IDs of the users to add.
     * @return bool True if the users were added successfully, false otherwise.
     */
    public function addCompetitionUsers(Competition $competition, array $user_ids): bool
    {
        try {
            $result = $this->transactionManager->run(function () use ($competition, $user_ids) {
                $this->competitionRepository->addUsersToCompetition($competition, $user_ids);
                return true;
            });

            $this->flasher->crudSuccess('saved');
            return $result;
        } catch (Exception $exception) {
            $this->registerLogs('Error adding users to competition: ', $exception);
            $this->flasher->crudFailure('saved');
            return false;
        }
    }

    /**
     * Remove a user from a competition.
     *
     * @param Competition $competition The ID of the competition.
     * @param int $user_id The ID of the user to remove.
     * @return bool True if the user was removed successfully, false otherwise.
     */
    public function removeCompetitionUser(Competition $competition, int $user_id): bool
    {
        try {
            if(!$competition->canEdit()) {
                $this->flasher->error(__('messages.validation.not_allow.competition_update'));
                return false;
            };
            $result = $this->transactionManager->run(function () use ($competition, $user_id) {
                $competition->users()->detach($user_id);
                return true;
            });

            $this->flasher->crudSuccess('deleted');
            return $result;
        } catch (Exception $exception) {
            $this->registerLogs('Error removing user from competition: ', $exception);
            $this->flasher->crudFailure('deleted');
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
        try {
            if (!$competition->canEdit()) {
                $this->flasher->error(__('messages.validation.not_allow.competition_update'));
                return false;
            }
            
            $result = $this->transactionManager->run(function () use ($competition, $auditor_ids) {
                $this->competitionRepository->addAuditorsToCompetition($competition, $auditor_ids);
                UserNotifyEmail::auditorNewCompetition($competition, $auditor_ids);
                return true;
            });

            $this->flasher->crudSuccess('saved');
            return $result;
        } catch (Exception $exception) {
            $this->registerLogs('Error adding auditors to competition: ', $exception);
            $this->flasher->crudFailure('saved');
            return false;
        }
    }

    /**
     * Remove an auditor from a competition.
     *
     * @param Competition $competition The competition object.
     * @param int $auditor_id The ID of the auditor to remove.
     * @return bool True if the auditor was removed successfully, false otherwise.
     */
    public function removeCompetitionAuditor(Competition $competition, int $auditor_id): bool
    {
        try {            
            if (!$competition->canEdit()) {
                $this->flasher->error(__('messages.validation.not_allow.competition_update'));
                return false;
            }
            if($competition->auditors->count() <= 1){
                $this->flasher->error(__('messages.validation.not_allow.remove_auditor_only_one'));
                return false;
            }
            $admin = Admin::find($auditor_id);

            $job = $this->createDeleteJob($admin, $competition);
            $this->jobTrackingService->dispatchWithTracking($job);

            $this->flasher->info(__('messages.validation.info.deleted'));
            return true;
        } catch (Exception $exception) {
            $this->registerLogs('Error removing auditor from competition: ', $exception);
            $this->flasher->crudFailure('deleted');
            return false;
        }
    }

    /**
     * Activate a competition.
     *
     * @param Competition $competition The competition to activate.
     * @return bool True if the competition was activated successfully, false otherwise.
     */
    public function activateCompetition(Competition $competition): bool
    {
        try {
            if ($competition->start_date->greaterThanOrEqualTo(now())){
                $this->flasher->error(__('messages.validation.not_allow.competition_activate_early'));
                return false;
            }
            if ($competition->levels->count() != $competition->levels_number){
                $this->flasher->error(__('messages.validation.not_allow.competition_activate_match_levels'));
                return false;
            }
            if ($competition->users->count() <= 2){
                $this->flasher->error(__('messages.validation.not_allow.competition_activate_less_competitors'));
                return false;
            }
            if ($competition->auditors->count() == 0){
                $this->flasher->error(__('messages.validation.not_allow.competition_activate_less_auditor'));
                return false;
            }
            if(!$competition->isAllLevelAfterNow()){
                $this->flasher->error(__('messages.validation.not_allow.competition_activate_level_pass'));
                return false;
            }

            $result = $this->transactionManager->run(function () use ($competition) {
                $competition->start_date = now();
                $competition->update(['status' => Competition::STATUS_ACTIVE]);
                UserNotifyEmail::usersActivateCompetition($competition);
                
                // Send notification to competition users
                $this->notificationService->competitionActivated($competition);
                
                return true;
            });

            $this->flasher->crudSuccess('activated');
            return $result;
        } catch (Exception $exception) {
            $this->registerLogs('Competition activation error: ', $exception);
            $this->flasher->crudFailure('activated');
            return false;
        }
    }

    /** helper methods */
    protected function createDeleteJob(Admin $admin, Competition $competition): SafeDeleteAuditorJob
    {
        return new SafeDeleteAuditorJob(
            auditor: $admin,
            competition: $competition,
            userId: Auth::id()
        );
    }
    
}
