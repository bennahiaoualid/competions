<?php

namespace App\Services\Competition;

use App\Http\Helpers\UserNotifyEmail;
use App\Interface\Competition\LevelRepositoryInterface;
use App\Models\Competition\Competition;
use App\Models\Competition\Level;
use App\Traits\RegisterLogs;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr; // For Arr::except

class LevelService
{
    use RegisterLogs;

    public function __construct(
        protected LevelRepositoryInterface $levelRepository
    ) {
    }

    public function create(array $data, Competition $competition): array
    {
        try {

            if ($this->levelRepository->checkCompetitionMaxLevelNumbers($competition)) {
                return ['status' => 'error', 'message_key' => 'messages.validation.not_allow.competition_max_levels'];
            }

            if ($competition->start_date->gt(Carbon::parse($data["start_date"]))) {
                return ['status' => 'error', 'message_key' => 'validation.custom.start_date_gt_competition'];
            }

            if ($this->levelRepository->hasTimeConflict($competition->id, $data["start_date"], $data["duration"])) {
                return ['status' => 'error', 'message_key' => 'messages.validation.not_allow.level_time_conflict'];
            }

            $level = $this->levelRepository->create(array_merge($data, ['competition_id' => $competition->id]));
            //UserNotifyEmail::adminLevel($level);
            return ['status' => 'success', 'level' => $level, 'message_key' => 'saved'];

        } catch (Exception $exception) {
            $this->registerLogs('LevelService creation error: ', $exception);
            return ['status' => 'exception', 'message_key' => 'saved'];
        }
    }

    public function getEditData(string $encodedId): array
    {
        try {
            $level = $this->levelRepository->findDecodedOrFail($encodedId);
            $admins = $this->levelRepository->getAllAdmins();
            return ['status' => 'success', 'level' => $level, 'admins' => $admins];
        } catch (Exception $exception) {
            $this->registerLogs('LevelService getEditData error: ', $exception);
            return ['status' => 'error', 'message_key' => 'fetch_error', 'exception' => 'something_went_wrong'];
        }
    }

    public function update(int $levelId, array $data): array
    {
        try {
            $level = $this->levelRepository->findOrFail($levelId);
            $competition = $level->competition; // Assuming competition relation is loaded or eager loaded

            if ($level->status != 0) {
                return ['status' => 'error', 'message_key' => 'messages.validation.not_allow.active_level_update'];
            }

            $originalStartDate = $level->start_date;
            $newStartDate = Carbon::parse($data['start_date']); // Ensure Carbon instance for comparison
            $startDateChanged = !$newStartDate->eq($originalStartDate);


            if ($startDateChanged) {
                 if ($this->levelRepository->hasTimeConflict($level->competition_id, $data['start_date'], $level->duration, $level->id)) {
                    return ['status' => 'error', 'message_key' => 'messages.validation.not_allow.level_activate_time_conflict'];
                }
            }
            
            // Ensure all provided data keys exist on the Level model or are fillable
            // For safety, only pick fields that are expected to be updated.
            $updateData = Arr::only($data, ['name', 'description', 'start_date', 'duration', 'admin_id']);


            $updated = $this->levelRepository->update($level, $updateData);

            if ($updated) {
                if ($startDateChanged) {
                    UserNotifyEmail::usersUpdateLevel($competition, $level);
                }
                return ['status' => 'success', 'level' => $level, 'message_key' => 'updated'];
            } else {
                return ['status' => 'error', 'message_key' => 'updated_error_generic'];
            }

        } catch (Exception $exception) {
            $this->registerLogs('LevelService update error: ', $exception);
            return ['status' => 'exception', 'message_key' => 'updated'];
        }
    }

    public function delete(int $levelId): array
    {
        try {
            $level = $this->levelRepository->findOrFail($levelId);
            $competition = $level->competition;

            if (!$this->levelRepository->canCompetitionBeEdited($competition)) {
                return ['status' => 'error', 'message_key' => 'messages.validation.not_allow.competition_update'];
            }

            if ($competition->status != 0) {
                return ['status' => 'error', 'message_key' => 'messages.validation.not_allow.active_competition_update'];
            }

            $deleted = $this->levelRepository->delete($level);
            if ($deleted) {
                return ['status' => 'success', 'message_key' => 'deleted'];
            } else {
                 return ['status' => 'error', 'message_key' => 'deleted_error_generic'];
            }

        } catch (Exception $exception) {
            $this->registerLogs('LevelService delete error: ', $exception);
            return ['status' => 'exception', 'message_key' => 'deleted'];
        }
    }

    public function activateLevel(int $levelId): array
    {
        try {
            $level = $this->levelRepository->findOrFail($levelId);
            $competition = $level->competition;

            if ($competition->status != 1) {
                return ['status' => 'error', 'message_key' => 'messages.validation.not_allow.level_activate_before_competition'];
            }
            if ($level->start_date->gte(now())) { // gte because original was lessThan(now()) for error
                return ['status' => 'error', 'message_key' => 'messages.validation.not_allow.level_activate_early'];
            }
            if ($this->levelRepository->getQuestionsCount($level) != $level->questions_number) {
                return ['status' => 'error', 'message_key' => 'messages.validation.not_allow.level_activate_match_questions'];
            }
            if (!$this->levelRepository->isLevelTheEarliest($level)) {
                return ['status' => 'error', 'message_key' => 'messages.validation.not_allow.level_activate_not_its_tour'];
            }
            if (!$this->levelRepository->isPreviousLevelAudited($level)) {
                return ['status' => 'error', 'message_key' => 'messages.validation.not_allow.level_activate_previous_not_audit'];
            }
            if (!$this->levelRepository->areAllCompetitionLevelsAfterNow($competition, $level)) {
                return ['status' => 'error', 'message_key' => 'messages.validation.not_allow.competition_activate_level_pass'];
            }

            $newStartDate = now();
            if ($this->levelRepository->hasTimeConflict($level->competition_id, $newStartDate->toDateTimeString(), $level->duration, $level->id)) {
                return ['status' => 'error', 'message_key' => 'messages.validation.not_allow.level_activate_time_conflict'];
            }
            if (!$this->levelRepository->canLevelBeEdited($level)) { // Original logic had this condition implicitly
                 return ['status' => 'error', 'message_key' => 'messages.validation.not_allow.level_edit_restricted']; // Generic message
            }

            $updated = $this->levelRepository->update($level, ['start_date' => $newStartDate, 'status' => "1"]);
            if ($updated) {
                UserNotifyEmail::usersActivateLevel($competition, $level);
                return ['status' => 'success', 'message_key' => 'activated'];
            } else {
                return ['status' => 'error', 'message_key' => 'activated_error_generic'];
            }

        } catch (Exception $exception) {
            $this->registerLogs('LevelService activateLevel error: ', $exception);
            return ['status' => 'exception', 'message_key' => 'activated'];
        }
    }

    public function finishLevel(int $levelId): array
    {
        try {
            DB::beginTransaction();
            $level = $this->levelRepository->findOrFail($levelId);
            $competition = $level->competition;

            if (!($level->status == 1 && !$this->levelRepository->isLevelStillActive($level))) {
                return ['status' => 'error', 'message_key' => 'messages.validation.not_allow.level_finish_still_active'];
            }
            if (!$this->levelRepository->canLevelBeEdited($level)) {
                 // This condition might need more specific error message if canLevelBeEdited has varied reasons
                return ['status' => 'error', 'message_key' => 'messages.validation.not_allow.level_edit_restricted_finish'];
            }

            $this->_fillEmptyQuestionResponses($level);
            $this->_assignUsersToAuditors($level);

            $updated = $this->levelRepository->update($level, ['status' => "2"]);

            if ($updated) {
                UserNotifyEmail::auditorsFinishLevel($competition, $level);
                DB::commit();
                return ['status' => 'success', 'message_key' => 'finish'];
            } else {
                DB::rollBack();
                return ['status' => 'error', 'message_key' => 'finish_error_generic'];
            }

        } catch (Exception $exception) {
            DB::rollBack();
            $this->registerLogs('LevelService finishLevel error: ', $exception);
            return ['status' => 'exception', 'message_key' => 'finish'];
        }
    }

    private function _fillEmptyQuestionResponses(Level $level): void
    {
        $users = $this->levelRepository->getUsersForCompetition($level->competition);
        if ($users->isEmpty()) {
            return;
        }

        foreach ($users as $user) {
            $unansweredQuestions = $this->levelRepository->getUnansweredQuestionsForUser($level, $user);
            $responsesData = [];
            foreach ($unansweredQuestions as $question) {
                $responsesData[] = [
                    'response_text' => '',
                    'question_id' => $question->id,
                    'user_id' => $user->id,
                    'admin_id' => null, // Assuming admin_id is nullable or not set here
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if (!empty($responsesData)) {
                $this->levelRepository->createMultipleResponses($responsesData);
            }
        }
    }

    private function _assignUsersToAuditors(Level $level): void
    {
        $users = $this->levelRepository->getUsersForCompetition($level->competition);
        $auditors = $this->levelRepository->getAuditorsForCompetition($level->competition);

        if ($users->isEmpty() || $auditors->isEmpty()) {
            return;
        }
        $this->levelRepository->assignAuditorsToUsersInPivot($level, $users, $auditors);
    }
}
