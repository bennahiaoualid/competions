<?php

namespace App\Services\Competition;

use Exception;
use Carbon\Carbon;
use App\Models\Admin\Admin;
use App\Traits\RegisterLogs;
use App\Helpers\UserNotifyEmail;
use App\Models\Competition\Level;
use App\Contracts\FlasherInterface;
use App\Enums\AdminApprovalTypeEnum;
use App\Models\Competition\Competition;
use App\Jobs\Competition\FinishLevelJob;
use App\Services\Admin\AdminApprovalService;
use App\Contracts\TransactionManagerInterface;
use Illuminate\Support\Arr; // For Arr::except
use App\Exceptions\AdminAlreadyDecidedException;
use App\Exceptions\AdminNotAvailableAsLevelManagerException;
use App\Interface\Competition\LevelRepositoryInterface;
use App\Services\Notification\OptimizedCompetitionNotificationService;

class LevelService
{
    use RegisterLogs;

    public function __construct(
        protected LevelRepositoryInterface $levelRepository,
        protected TransactionManagerInterface $transactionManager,
        protected FlasherInterface $flasher,
        protected OptimizedCompetitionNotificationService $notificationService,
        protected AdminApprovalService $approvalService
    ) {
    }

    /**
     * Create a new level
     * @param array $data
     * @param Competition $competition
     * @return bool
     */
    public function create(array $data, Competition $competition): bool
    {
        try {
            if ($competition->hasReachedMaxLevels()) {
                $this->flasher->error(__('messages.validation.not_allow.competition_max_levels'));
                return false;
            }

            if ($competition->start_date->gt(Carbon::parse($data["start_date"]))) {
                $this->flasher->error(__('validation.custom.start_date_gt_competition'));
                return false;
            }

            if ($this->levelRepository->hasTimeConflict($competition->id, $data["start_date"], $data["duration"])) {
                $this->flasher->error(__('messages.validation.not_allow.level_time_conflict'));
                return false;
            }

            // Remove admin_id from data - it will be set via approval
            $adminId = $data['admin_id'] ?? null;
            unset($data['admin_id']);

            $level = $this->transactionManager->run(function () use ($data, $competition, $adminId) {
                $level = $this->levelRepository->create(array_merge($data, [
                    'competition_id' => $competition->id,
                    'admin_id' => null // Will be set via approval
                ]));

                // If admin_id was provided, create approval request
                if ($adminId) {
                    $this->requestLevelManagerAssignment($level, $adminId);
                }
                return $level;
            });

            // Send notification to competition users
            $this->notificationService->levelCreated($competition, $level);

            $this->flasher->crudSuccess('saved');
            return true;

        } catch (AdminNotAvailableAsLevelManagerException $exception) {
            $this->registerLogs('LevelService creation error: ', $exception);
            $this->flasher->error($exception->getTransMessage());
            return false;
        } catch (Exception $exception) {
            $this->registerLogs('LevelService creation error: ', $exception);
            $this->flasher->crudFailure('saved');
            return false;
        }
    }

    /**
     * Request level manager assignment (creates approval instead of direct assignment)
     */
    public function requestLevelManagerAssignment(Level $level, int $adminId, bool $deletePending = false): bool
    {
        if(!$this->levelRepository->isAdminAllowedToBeLevelManager($adminId)){
            throw new AdminNotAvailableAsLevelManagerException($adminId);
        }

        $adminExistsApproval = $this->approvalService->getApprovalStatus($adminId, Level::class, $level->id, AdminApprovalTypeEnum::LEVEL_MANAGER->value);

        if($adminExistsApproval['rejected'] > 0 || $adminExistsApproval['approved'] > 0){
            throw new AdminAlreadyDecidedException(
                $adminId, 
                Level::class, 
                $level->id, 
                AdminApprovalTypeEnum::LEVEL_MANAGER->value,
            );
        }elseif($adminExistsApproval['pending'] > 0 && $deletePending){
            $this->approvalService->removePendingRequests(Level::class, $level->id, AdminApprovalTypeEnum::LEVEL_MANAGER->value);
        }
        
        $admin = $this->approvalService->createApprovalRequest(
            adminId: $adminId,
            entityType: Level::class,
            entityId: $level->id,
            type: AdminApprovalTypeEnum::LEVEL_MANAGER
        );

        // Notify admin about level manager request
        if($admin){
            $this->notificationService->levelManagerRequested($level, $admin);
            $this->flasher->success(__('messages.validation.success.level_manager_requested', ['admin' => $admin->name]));
        }

        return true;
    }

    /**
     * Direct assignment (used by handler)
     */
    public function assignLevelManager(Level $level, int $adminId): bool
    {
        try {
            $level->update(['admin_id' => $adminId]);
            UserNotifyEmail::adminLevel($level);
            return true;
        } catch (Exception $exception) {
            $this->registerLogs('LevelService Error assigning level manager: ', $exception);
            return false;
        }
    }

    /**
     * Get the edit data for a level
     * @param string $encodedId
     * @return array
     */
    public function getEditData(string $encodedId): array
    {
        try {
            $level = Level::findOrFail(base64_decode($encodedId));
            $admins = Admin::availableAsLevelManager()->get();
            return ['status' => 'success', 'level' => $level, 'admins' => $admins];
        } catch (Exception $exception) {
            $this->registerLogs('LevelService getEditData error: ', $exception);
            $this->flasher->error(__('messages.fetch_error_detailed'));
            return ['status' => 'error'];
        }
    }

    /**
     * Update a level
     * @param Level $level
     * @param array $data
     * @return bool
     */
    public function update(Level $level, array $data): bool
    {
        try {
            $competition = $level->competition;

            if($competition->status != Competition::STATUS_PENDING){
                $this->flasher->error(__('messages.validation.not_allow.active_competition_update'));
                return false;
            }

            if ($level->status != Level::STATUS_PENDING) {
                $this->flasher->error(__('messages.validation.not_allow.active_level_update'));
                return false;
            }

            $originalStartDate = $level->start_date;
            $newStartDate = Carbon::parse($data['start_date']);
            $startDateChanged = !$newStartDate->eq($originalStartDate);

            if ($startDateChanged) {
                if ($this->levelRepository->hasTimeConflict($level->competition_id, $data['start_date'], $level->duration, $level->id)) {
                    $this->flasher->error(__('messages.validation.not_allow.level_activate_time_conflict'));
                    return false;
                }
                if($newStartDate->lt($competition->start_date)){
                    $this->flasher->error(__('validation.custom.start_date_gt_competition'));
                    return false;
                }
            }

            $updateData = Arr::only($data, ['name', 'description', 'start_date', 'duration']);
            
            $this->transactionManager->run(function () use ($data, $level, $updateData, $competition, $startDateChanged) {
                $updated = $this->levelRepository->update($level, $updateData);
                if ($updated && $startDateChanged) {
                    UserNotifyEmail::usersUpdateLevel($competition, $level);
                }
                if($level->admin_id != $data['admin_id'] && $updated){
                    $this->requestLevelManagerAssignment($level, $data['admin_id'], deletePending: true);
                }
                return $updated;
            });

            // Send notification to competition users
            $this->notificationService->levelUpdated($competition, $level);

            

            $this->flasher->crudSuccess('updated');
            return true;

        } catch (AdminNotAvailableAsLevelManagerException $exception) {
            $this->registerLogs('LevelService update error: ', $exception);
            $this->flasher->error($exception->getTransMessage());
            return false;
        } catch (AdminAlreadyDecidedException $exception) {
            $this->registerLogs('LevelService update error: ', $exception);
            $this->flasher->error($exception->getTransMessage());
            return false;
        } catch (Exception $exception) {
            $this->registerLogs('LevelService update error: ', $exception);
            $this->flasher->crudFailure('updated');
            return false;
        }
    }

    /**
     * Delete a level
     * @param Level $level
     * @return bool
     */
    public function delete(Level $level): bool
    {
        try {
            $competition = $level->competition;

            if (!$level->canEdit()) {
                $this->flasher->error(__('messages.validation.not_allow.competition_update'));
                return false;
            }

            if ($competition->status != Competition::STATUS_PENDING) {
                $this->flasher->error(__('messages.validation.not_allow.active_competition_update'));
                return false;
            }

            $this->levelRepository->delete($level);
            $this->flasher->crudSuccess('deleted');

            return true;

        } catch (Exception $exception) {
            $this->registerLogs('LevelService delete error: ', $exception);
            $this->flasher->crudFailure('deleted');
            return false;
        }
    }

    public function activateLevel(Level $level): bool
    {
        try {
            $competition = $level->competition;

            if($level->status != Level::STATUS_PENDING){
                return false;
            }

            if (!$level->canEdit()) {
                $this->flasher->error(__('messages.validation.not_allow.competition_update'));
                return false;
            }

            if ($competition->status != Competition::STATUS_ACTIVE) {
                $this->flasher->error(__('messages.validation.not_allow.level_activate_before_competition'));
                return false;
            }
            if ($level->start_date->gte(now())) {
                $this->flasher->error(__('messages.validation.not_allow.level_activate_early'));
                return false;
            }
            if ($level->questions->count() != $level->questions_number) {
                $this->flasher->error(__('messages.validation.not_allow.level_activate_match_questions'));
                return false;
            }
            if (!$level->isTheEarliest()) {
                $this->flasher->error(__('messages.validation.not_allow.level_activate_not_its_tour'));
                return false;
            }
            if (!$level->isThePreviousAudit()) {
                $this->flasher->error(__('messages.validation.not_allow.level_activate_previous_not_audit'));
                return false;
            }
            if (!$competition->isAllLevelAfterNow($level->id)) {
                $this->flasher->error(__('messages.validation.not_allow.competition_activate_level_pass'));
                return false;
            }

            $newStartDate = now();
            if ($this->levelRepository->hasTimeConflict($level->competition_id, $newStartDate->format('Y-m-d H:i'), $level->duration, $level->id)) {
                $this->flasher->error(__('messages.validation.not_allow.level_activate_time_conflict'));
                return false;
            }

            $updated = $this->levelRepository->update($level, ['start_date' => $newStartDate, 'status' => "active"]);
            
            if ($updated) {
                UserNotifyEmail::usersActivateLevel($competition, $level);
                
                // Send notification to competition users
                $this->notificationService->levelActivated($competition, $level);
            }

            $this->flasher->crudSuccess('activated');
            return true;

        } catch (Exception $exception) {
            $this->registerLogs('LevelService activateLevel error: ', $exception);
            $this->flasher->crudFailure(false, 'activated');
            return false;
        }
    }

    public function finishLevel(Level $level): bool
    {    
        if (!$level->canEdit()) {
            $this->flasher->error(__('messages.validation.not_allow.level_edit_restricted_finish'));
            return false;
        }
    
        if (!($level->status == Level::STATUS_ACTIVE && !$level->isStillActive())) {
            $this->flasher->error(__('messages.validation.not_allow.level_finish_still_active'));
            return false;
        }
        
        FinishLevelJob::dispatchSync($level);
        
        // Send notification to competition users
        $this->notificationService->levelFinished($level->competition, $level);
        
        return true;
    }
}
