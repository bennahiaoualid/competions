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
        return Level::create($data);
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
     * check if the timing of the new level is conflict with the other levels in the same competition
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


    /**
     * insert missing responses for level
     * detect users who didn't respond to the level questions then fill them with empty responses
     * @param Level $level
     * @param int $batchSize default 500 to avoid memory issues
     * @return void
     */
    public function insertMissingResponsesForLevel(Level $level, int $batchSize = 500): void
    {
        $rows = DB::table('competition_user as cu')
            ->where('cu.competition_id', $level->competition_id)
            ->crossJoin('questions as q', function ($join) use ($level) {
                $join->on('q.level_id', DB::raw($level->id));
            })
            ->leftJoin('responses as r', function ($join) {
                $join->on('r.user_id', '=', 'cu.user_id')
                    ->on('r.question_id', '=', 'q.id');
            })
            ->whereNull('r.id')
            ->select([
                DB::raw("'' as response_text"),
                'q.id as question_id',
                'cu.user_id',
                DB::raw('NULL as admin_id'),
                DB::raw('CURRENT_TIMESTAMP as created_at'),
                DB::raw('CURRENT_TIMESTAMP as updated_at'),
            ])
            ->cursor(); 
    
        $buffer = [];
        foreach ($rows as $row) {
            $buffer[] = (array) $row;
    
            if (count($buffer) >= $batchSize) {
                DB::table('responses')->insert($buffer);
                $buffer = [];
            }
        }
    
        if (!empty($buffer)) {
            DB::table('responses')->insert($buffer);
        }
    }

    public function assignAuditorsToUsersInPivot(Level $level, Collection $users, Collection $auditors): void
    {
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

    public function isAdminAllowedToBeLevelManager($adminID) : bool 
    {
        return Admin::availableAsLevelManager()->where('id', $adminID)->exists();
    }
}
