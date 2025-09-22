<?php

namespace App\Services\Competition;

use Exception;
use App\Models\Admin\Admin;
use Illuminate\Support\Facades\DB;
use App\Contracts\FlasherInterface;
use App\Enums\AdminApprovalTypeEnum;
use Illuminate\Support\Facades\Auth;
use App\Services\SystemSettingService;
use App\Models\Competition\Competition;
use App\Services\Admin\AdminApprovalService;
use App\Contracts\TransactionManagerInterface;
use App\Jobs\Competition\SafeDeleteAuditorJob;
use App\Services\Monitoring\JobTrackingService;
use App\Services\Payment\CoinTransactionService;
use App\Traits\RegisterLogs; // For logging errors
use App\Jobs\Competetion\SyncCompetitionParticipants;
use App\Helpers\UserNotifyEmail; // For sending emails
use App\Exceptions\AIQuestionGeneration\PaidServiceException;
use App\Interface\Competition\CompetitionRepositoryInterface;
use App\Traits\CrudOperationNotificationAlert; // For notifications
use App\Services\Notification\OptimizedCompetitionNotificationService;
use App\Models\User; // For Auth::user() type hinting if specific methods are used
use App\Services\CashManagment\CompetitionCacheManagmentSystem;

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
        protected OptimizedCompetitionNotificationService $notificationService,
        protected AdminApprovalService $approvalService,
        protected SystemSettingService $systemSettingService,
        protected CoinTransactionService $coinTransactionService,
        protected CompetitionCacheManagmentSystem $competitionCacheManagment,
    ) {
    }

    /**
     * Validate if admin has sufficient balance for multi-winner competition
     *
     * @param int $winnerCoins The number of coins for the winner
     * @param bool $multiWinner Whether this is a multi-winner competition
     * @return bool True if balance is sufficient
     * @throws PaidServiceException If insufficient balance
     */
    protected function validateMultiWinnerBalance(int $winnerCoins, bool $multiWinner): int
    {
        // Get current admin user
        $admin = Auth::user();
        if (!$admin || !$admin->coinBalance) {
            throw PaidServiceException::insufficientBalance(0, 0, $admin->id ?? 0);
        }

        $totalCoinsNeeded = $winnerCoins;

        $userBalance = $admin->coinBalance->balance;

        if ($multiWinner) {

            // Get system settings for percentages
            $secondPlacePercentage = $this->systemSettingService->getValueAsInt('second_place_winner_percentage', 50);
            $thirdPlacePercentage = $this->systemSettingService->getValueAsInt('third_place_winner_percentage', 20);

            // Calculate total coins needed
            $secondPlaceCoins = round($winnerCoins * ($secondPlacePercentage / 100));
            $thirdPlaceCoins = round($winnerCoins * ($thirdPlacePercentage / 100));
            $totalCoinsNeeded = $winnerCoins + $secondPlaceCoins + $thirdPlaceCoins;
        }

        // Check if balance is sufficient
        if ($userBalance < $totalCoinsNeeded) {
            throw PaidServiceException::insufficientBalance($totalCoinsNeeded, $userBalance, $admin->id);
        }

        return intval($totalCoinsNeeded);
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
            $auth_admin = Auth::user();
            // Validate balance for multi-winner competitions before creating
            $winnerCoins = (int)($data['winner_gifts'] ?? 0);
            $multiWinner = (bool)($data['multi_winner'] ?? false);

            $competitionGift = $this->systemSettingService->getValueAsInt('min_competition_coins');

            if($winnerCoins < $competitionGift){
                $this->flasher->error(
                    __('messages.validation.not_allow.competition_create_less_gift', ['gift' => $competitionGift])
                );
                return false;
            }
            $totalCoinsNeeded = 0;
            if ($winnerCoins > 0) {
                $totalCoinsNeeded = $this->validateMultiWinnerBalance($winnerCoins, $multiWinner);
            }

            $result = $this->transactionManager->run(function () use ($data, $totalCoinsNeeded, $auth_admin) {
                
                $competition = Competition::create(array_merge($data, ['admin_id' => $auth_admin->id]));
                
                SyncCompetitionParticipants::dispatch($competition)->afterCommit();
                
                //Deduct coins from admin balance
                $auth_admin->coinBalance->spendCoins($totalCoinsNeeded);

                DB::afterCommit(function () use ($totalCoinsNeeded, $competition, $auth_admin) {
                    $this->coinTransactionService->createCompetitionWinnerGiftTransaction($auth_admin, $totalCoinsNeeded);
                    // Send notification to eligible users
                    $this->notificationService->competitionCreated($competition);
                });
                return true;
            });
            $this->competitionCacheManagment->invalidateUsersComptitionInfo();
            $this->flasher->crudSuccess('saved');
            return $result;
        } catch (PaidServiceException $exception) {
            // Handle insufficient balance specifically
            $this->flasher->error(__('messages.validation.not_allow.service_insufficient_balance'));
            return false;
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
                // ivalidate compeition detail data cache and comptitions list
                $this->competitionCacheManagment->invalidateUsersComptitionInfo();
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

            $this->competitionCacheManagment->invalidateUsersComptitionInfo();


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
                
                // Get the newly added users for notifications
                $newUsers = User::whereIn('id', $user_ids)->get();
                
                // Send notification to newly added users
                $this->notificationService->notifyUsers($newUsers, $competition, 'user_added');
                
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
     * Request auditor assignment (creates approval instead of direct assignment)
     * @param Competition $competition The competition object.
     * @param array $auditorIds The IDs of the auditors to add.
     * @return bool True if the auditors were added successfully, false otherwise.
     */
    public function requestAuditorAssignment(Competition $competition, array $auditorIds): bool
    {
        try {
            if (!$competition->canEdit()) {
                $this->flasher->error(__('messages.validation.not_allow.competition_update'));
                return false;
            }
            $result = $this->transactionManager->run(function () use ($competition, $auditorIds) {
                // Check if any of the auditors have an existing approval request
                $approvalStatus = $this->approvalService->getApprovalStatusForMultipleAdmins(
                    adminIds: $auditorIds,
                    entityType: Competition::class,
                    entityId: $competition->id,
                    type: AdminApprovalTypeEnum::AUDITOR->value
                );
                
                if(count($approvalStatus['pending']) > 0){
                    $this->flasher->error(__('messages.validation.error.auditor_assignment_requested_pending', ['admins' => implode(', ', array_column($approvalStatus['pending'], 'name'))]));
                }
                if(count($approvalStatus['rejected']) > 0){
                    $this->flasher->error(__('messages.validation.error.auditor_assignment_requested_rejected', ['admins' => implode(', ', array_column($approvalStatus['rejected'], 'name'))]));
                }
                if(count($approvalStatus['approved']) > 0){
                    $this->flasher->error(__('messages.validation.error.auditor_assignment_requested_approved', ['admins' => implode(', ', array_column($approvalStatus['approved'], 'name'))]));
                }

                if(count($approvalStatus['new']) > 0){
                    $adminIds = array_column($approvalStatus['new'], 'id');
                    $adminApprovalRequests = $this->approvalService->insertBulkApprovalRequests(
                        adminIds: $adminIds,
                        entityType: Competition::class,
                        entityId: $competition->id,
                        type: AdminApprovalTypeEnum::AUDITOR
                    );
                    // Notify all admins about auditor request
                    if ($adminApprovalRequests->isNotEmpty()) {
                        $this->flasher->info(__('messages.validation.success.auditor_assignment_requested', ['admins' => implode(', ', $adminApprovalRequests->pluck('name')->toArray())]));
                        $this->notificationService->auditorRequestedBulk($competition, $adminApprovalRequests);
                    }
                    return true;
                }
                return false;
            });

            return $result;
        } catch (Exception $exception) {
            $this->registerLogs('Error requesting auditor assignment: ', $exception);
            $this->flasher->crudFailure('saved');
            return false;
        }
    }

    /**
     * Assign auditor to a competition
     */
    public function assignAuditor(Competition $competition, int $adminId): bool
    {
        try {
            $competition->auditors()->attach($adminId);
            UserNotifyEmail::auditorNewCompetition($competition, [$adminId]);
            return true;
        } catch (Exception $exception) {
            $this->registerLogs('Competition Service Error assigning auditor: ', $exception);
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
            $this->competitionCacheManagment->invalidateUsersComptitionInfo();

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
