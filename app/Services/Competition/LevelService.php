<?php

namespace App\Services\Competition;

use Exception;
use Carbon\Carbon;
use App\Models\Admin\Admin;
use App\Traits\RegisterLogs;
use App\Models\Competition\Level;
use App\Contracts\FlasherInterface;
use App\Helpers\UserNotifyEmail;
use App\Models\Competition\Competition;
use App\Jobs\Competition\FinishLevelJob;
use App\Contracts\TransactionManagerInterface;
use Illuminate\Support\Arr; // For Arr::except
use App\Interface\Competition\LevelRepositoryInterface;

class LevelService
{
    use RegisterLogs;

    public function __construct(
        protected LevelRepositoryInterface $levelRepository,
        protected TransactionManagerInterface $transactionManager,
        protected FlasherInterface $flasher
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
            if(!$this->levelRepository->isAdminAllowedToBeLevelManager($data['admin_id'])){
                return false;
            }
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

            $level = $this->levelRepository->create(array_merge($data, ['competition_id' => $competition->id]));
            UserNotifyEmail::adminLevel($level);

            $this->flasher->crudSuccess('saved');
            return true;

        } catch (Exception $exception) {
            $this->registerLogs('LevelService creation error: ', $exception);
            $this->flasher->crudFailure('saved');
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

            if(!$this->levelRepository->isAdminAllowedToBeLevelManager($data['admin_id'])){
                return false;
            }

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

            $updateData = Arr::only($data, ['name', 'description', 'start_date', 'duration', 'admin_id']);

            $updated = $this->levelRepository->update($level, $updateData);
            if ($updated && $startDateChanged) {
                UserNotifyEmail::usersUpdateLevel($competition, $level);
            }

            $this->flasher->crudSuccess('updated');
            return true;

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
        return true;
    }
}
