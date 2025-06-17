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
            if ($this->levelRepository->checkCompetitionMaxLevelNumbers($competition)) {
                $this->flasher->notify(__('messages.validation.not_allow.competition_max_levels'), 'error');
                return false;
            }

            if ($competition->start_date->gt(Carbon::parse($data["start_date"]))) {
                $this->flasher->notify(__('validation.custom.start_date_gt_competition'), 'error');
                return false;
            }

            if ($this->levelRepository->hasTimeConflict($competition->id, $data["start_date"], $data["duration"])) {
                $this->flasher->notify(__('messages.validation.not_allow.level_time_conflict'), 'error');
                return false;
            }

            $level = $this->levelRepository->create(array_merge($data, ['competition_id' => $competition->id]));
            UserNotifyEmail::adminLevel($level);

            $this->flasher->notifyCrudResult(true, 'saved');
            return true;

        } catch (Exception $exception) {
            $this->registerLogs('LevelService creation error: ', $exception);
            $this->flasher->notifyCrudResult(false, 'saved');
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
            $admins = Admin::all();
            return ['status' => 'success', 'level' => $level, 'admins' => $admins];
        } catch (Exception $exception) {
            $this->registerLogs('LevelService getEditData error: ', $exception);
            $this->flasher->notify(__('messages.fetch_error_detailed'), 'error');
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

            if($competition->status != 0){
                $this->flasher->notify(__('messages.validation.not_allow.active_competition_update'), 'error');
                return false;
            }

            if ($level->status != 0) {
                $this->flasher->notify(__('messages.validation.not_allow.active_level_update'), 'error');
                return false;
            }

            $originalStartDate = $level->start_date;
            $newStartDate = Carbon::parse($data['start_date']);
            $startDateChanged = !$newStartDate->eq($originalStartDate);

            if ($startDateChanged) {
                if ($this->levelRepository->hasTimeConflict($level->competition_id, $data['start_date'], $level->duration, $level->id)) {
                    $this->flasher->notify(__('messages.validation.not_allow.level_activate_time_conflict'), 'error');
                    return false;
                }
                if($newStartDate->lt($competition->start_date)){
                    $this->flasher->notify(__('validation.custom.start_date_gt_competition'), 'error');
                    return false;
                }
            }

            $updateData = Arr::only($data, ['name', 'description', 'start_date', 'duration', 'admin_id']);

            $updated = $this->levelRepository->update($level, $updateData);
            if ($updated && $startDateChanged) {
                UserNotifyEmail::usersUpdateLevel($competition, $level);
            }

            $this->flasher->notifyCrudResult(true, 'updated');
            return true;

        } catch (Exception $exception) {
            $this->registerLogs('LevelService update error: ', $exception);
            $this->flasher->notifyCrudResult(false, 'updated');
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
                $this->flasher->notify(__('messages.validation.not_allow.competition_update'), 'error');
                return false;
            }

            if ($competition->status != 0) {
                $this->flasher->notify(__('messages.validation.not_allow.active_competition_update'), 'error');
                return false;
            }

            $this->levelRepository->delete($level);
            $this->flasher->notifyCrudResult(true, 'deleted');

            return true;

        } catch (Exception $exception) {
            $this->registerLogs('LevelService delete error: ', $exception);
            $this->flasher->notifyCrudResult(false, 'deleted');
            return false;
        }
    }

    public function activateLevel(Level $level): bool
    {
        try {
            $competition = $level->competition;

            if($level->status == 1){
                return false;
            }

            if (!$level->canEdit()) {
                $this->flasher->notify(__('messages.validation.not_allow.competition_update'), 'error');
                return false;
            }

            if ($competition->status != 1) {
                $this->flasher->notify(__('messages.validation.not_allow.level_activate_before_competition'), 'error');
                return false;
            }
            if ($level->start_date->gte(now())) {
                $this->flasher->notify(__('messages.validation.not_allow.level_activate_early'), 'error');
                return false;
            }
            if ($level->questions->count() != $level->questions_number) {
                $this->flasher->notify(__('messages.validation.not_allow.level_activate_match_questions'), 'error');
                return false;
            }
            if (!$level->isTheEarliest()) {
                $this->flasher->notify(__('messages.validation.not_allow.level_activate_not_its_tour'), 'error');
                return false;
            }
            if (!$level->isThePreviousAudit()) {
                $this->flasher->notify(__('messages.validation.not_allow.level_activate_previous_not_audit'), 'error');
                return false;
            }
            if (!$competition->isAllLevelAfterNow($level->id)) {
                $this->flasher->notify(__('messages.validation.not_allow.competition_activate_level_pass'), 'error');
                return false;
            }

            $newStartDate = now();
            if ($this->levelRepository->hasTimeConflict($level->competition_id, $newStartDate->format('Y-m-d H:i'), $level->duration, $level->id)) {
                $this->flasher->notify(__('messages.validation.not_allow.level_activate_time_conflict'), 'error');
                return false;
            }

            $updated = $this->levelRepository->update($level, ['start_date' => $newStartDate, 'status' => "1"]);
            
            if ($updated) {
                UserNotifyEmail::usersActivateLevel($competition, $level);
            }

            $this->flasher->notifyCrudResult(true, 'activated');
            return true;

        } catch (Exception $exception) {
            $this->registerLogs('LevelService activateLevel error: ', $exception);
            $this->flasher->notifyCrudResult(false, 'activated');
            return false;
        }
    }

    public function finishLevel(Level $level): bool
    {    
        if (!$level->canEdit()) {
            $this->flasher->notify(__('messages.validation.not_allow.level_edit_restricted_finish'), 'error');
            return false;
        }
    
        if (!($level->status == 1 && !$level->isStillActive())) {
            $this->flasher->notify(__('messages.validation.not_allow.level_finish_still_active'), 'error');
            return false;
        }
    
        FinishLevelJob::dispatchSync($level);
        return true;
    }
}
