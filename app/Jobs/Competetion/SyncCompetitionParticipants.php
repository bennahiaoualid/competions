<?php

namespace App\Jobs\Competetion;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use App\Models\Competition\Competition;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use App\Http\Helpers\UserNotifyEmail;

class SyncCompetitionParticipants implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Competition $competition,
        public bool $isUpdate = false
    ) {}

    public function handle(): void
    {
        $this->competition = $this->competition->fresh();
        $this->competition->update(['participants_sync_status' => 'in_progress']);

        $ageMin = $this->competition->age_start;
        $ageMax = $this->competition->age_end;
        $competitionId = $this->competition->id;

        $now = now();

        // Get new eligible user IDs
        $newEligibleUserIds = User::eligibleForCompetition($ageMin, $ageMax)->pluck('id');

        if ($this->isUpdate) {
            $existingUserIds = $this->competition->users()->pluck('users.id');

            $toDetach = $existingUserIds->diff($newEligibleUserIds);
            $toAttach = $newEligibleUserIds->diff($existingUserIds);

            if ($toDetach->isNotEmpty()) {
                $this->competition->users()->detach($toDetach);
            }

            if ($toAttach->isNotEmpty()) {
                $this->bulkAttach($competitionId, $toAttach, $now);
            }
        } else {
            $this->bulkAttach($competitionId, $newEligibleUserIds, $now);
        }

        $this->competition->update([
            'participants_sync_status' => 'completed',
            'last_synced_at' => now(),
        ]);

        // Refresh the competition model again to ensure it has the latest users relation populated
        // before sending notifications, especially if bulkAttach doesn't update the in-memory model.
        $this->competition = $this->competition->fresh('users');

        // Send notifications only if this is not an update operation (i.e., for new competitions)
        // Or, if you want to notify on updates too, you might call usersUpdateCompetition here.
        // For now, sticking to the original request for new competition notifications.
        if (!$this->isUpdate) {
            UserNotifyEmail::usersNewCompetition($this->competition);
        }else{
            UserNotifyEmail::usersUpdateCompetition($this->competition);
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->competition->update(['participants_sync_status' => 'failed']);
    }

    /**
     * Insert users into the pivot table in bulk and batches.
     */
    protected function bulkAttach(int $competitionId, Collection $userIds, $timestamp): void
    {
        $chunkSize = 500;

        $userIds->chunk($chunkSize)->each(function ($chunk) use ($competitionId, $timestamp) {
            $rows = $chunk->map(fn ($userId) => [
                'competition_id' => $competitionId,
                'user_id' => $userId,
            ])->toArray();

            DB::table('competition_user')->insert($rows);
        });
    }
}
