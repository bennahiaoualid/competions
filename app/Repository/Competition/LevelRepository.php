<?php

namespace App\Repository\Competition;

use App\Interface\Competition\LevelRepositoryInterface;
use App\Models\Admin\Admin;
use App\Models\Competition\Competition;
use App\Models\Competition\Level;
use App\Models\Competition\Question;
use App\Models\Competition\Response;
use App\Models\User; // Assuming User model exists and is needed for types
use App\Traits\RegisterLogs;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LevelRepository implements LevelRepositoryInterface
{
    use RegisterLogs;

    public function create(array $data): Level
    {
        try {
            return Level::create($data);
        } catch (Exception $exception) {
            $this->registerLogs('Level creation DB error: ', $exception);
            throw $exception; // Re-throw for service layer to handle
        }
    }

    public function findById(int $id): ?Level
    {
        return Level::find($id);
    }

    public function findOrFail(int $id): Level
    {
        return Level::findOrFail($id);
    }
    
    public function findDecodedOrFail(string $encodedId): Level
    {
        return Level::findOrFail(base64_decode($encodedId));
    }

    public function getAllAdmins(): Collection
    {
        return Admin::all();
    }

    public function update(Level $level, array $data): bool
    {
        try {
            return $level->update($data);
        } catch (Exception $exception) {
            $this->registerLogs('Level updating DB error: ', $exception);
            throw $exception; // Re-throw for service layer to handle
        }
    }

    public function delete(Level $level): bool
    {
        try {
            return $level->delete();
        } catch (Exception $exception) {
            $this->registerLogs('Level deleting DB error: ', $exception);
            throw $exception; // Re-throw for service layer to handle
        }
    }

    /**
     * check if the competition already get maximum number of levels
     * @param $competitionId
     * @return boolean
     */
    public function checkCompetitionMaxLevelNumbers(int $competitionId): bool
    {
        $competition = Competition::with('levels')->find($competitionId);
        if($competition->levels->count() == $competition->levels_number){
            return true;
        }
        return false;
    }

    /**
     * check if the timing of the new level is conflict with the previews level in the same competition
     * @param Level $level
     * @param int $competitionId
     * @param string $startDate
     * @param int $duration
     * @param int|null $excludeLevelId
     * @return boolean
     */
    public function hasTimeConflict(int $competitionId, string $startDate, int $duration, ?int $excludeLevelId = null): bool
    {
        $newStartDate = Carbon::parse($startDate);
        $newDuration = intval($duration);
        $newEndDate = $newStartDate->copy()->addMinutes($newDuration);

        $existingLevels = Level::where('competition_id', $competitionId)->get();

        foreach ($existingLevels as $level) {
            if($excludeLevelId != null && $level->id == $excludeLevelId) {
                continue;
            }
                $levelStartDate = $level->start_date;
                $levelEndDate = $level->start_date->copy()->addMinutes($level->duration);
                // Check if the new level overlaps with the existing level
                if (
                    ($newStartDate->between($levelStartDate, $levelEndDate)) ||
                    ($newEndDate->between($levelStartDate, $levelEndDate)) ||
                    ($levelStartDate->between($newStartDate, $newEndDate)) ||
                    ($levelEndDate->between($newStartDate, $newEndDate))
                ) {
                    return true; // Conflict found
                }

        }

        return false; // No conflict
    }

    public function getQuestionsCount(Level $level): int
    {
        return $level->questions()->count();
    }

    public function getUnansweredQuestionsForUser(Level $level, User $user): Collection
    {
        return Question::where('level_id', $level->id)
            ->whereDoesntHave('responses', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->get();
    }

    public function createMultipleResponses(array $responsesData): void
    {
        try {
            // Assuming $responsesData is an array of arrays, each with keys for Response model
            Response::insert($responsesData); // More efficient for multiple inserts
        } catch (Exception $exception) {
            $this->registerLogs('Error creating multiple responses: ', $exception);
            throw $exception;
        }
    }

    public function assignAuditorsToUsersInPivot(Level $level, Collection $users, Collection $auditors): void
    {
        if ($users->isEmpty() || $auditors->isEmpty()) {
            return;
        }

        $assignments = [];
        $auditorCount = $auditors->count();
        $index = 0;
        $shuffledUsers = $users->shuffle();

        foreach ($shuffledUsers as $user) {
            $auditor = $auditors[$index % $auditorCount];
            $assignments[] = [
                'level_id' => $level->id,
                'user_id' => $user->id,
                'admin_id' => $auditor->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $index++;
        }

        try {
            DB::table('level_admin_user')->insert($assignments);
        } catch (Exception $exception) {
            $this->registerLogs('Error assigning users to auditors in pivot: ', $exception);
            throw $exception;
        }
    }

    public function getUsersForCompetition(Competition $competition): Collection
    {
        return $competition->users; // Assumes 'users' relationship exists on Competition
    }

    public function getAuditorsForCompetition(Competition $competition): Collection
    {
        return $competition->auditors; // Assumes 'auditors' relationship exists on Competition
    }

    // Methods related to Level model's internal state checks.
    // If these methods on the model itself perform DB queries, they should be here.
    // If they are pure PHP logic on loaded attributes, the service can call them on the model instance.
    // For now, assuming they might be complex or involve DB queries.

    public function isLevelTheEarliest(Level $level): bool
    {
        return $level->isTheEarliest();
    }

    public function isPreviousLevelAudited(Level $level): bool
    {
        return $level->isThePreviousAudit();
    }

    public function areAllCompetitionLevelsAfterNow(Competition $competition, Level $currentLevel): bool
    {
        return $competition->isAllLevelAfterNow($currentLevel->id);
    }

    public function canLevelBeEdited(Level $level): bool
    {
        // This implies a check on the competition's state too.
        // $level->canEdit() original implementation likely checks $level->competition->canEdit()
        return $level->canEdit();
    }

    public function isLevelStillActive(Level $level): bool
    {
        return $level->isStillActive();
    }
    
    public function canCompetitionBeEdited(Competition $competition): bool
    {
        return $competition->canEdit();
    }

}
