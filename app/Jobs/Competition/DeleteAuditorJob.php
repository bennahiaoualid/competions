<?php

namespace App\Jobs\Competition;

use App\Models\Competition\Competition;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteAuditorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected int $auditorId,
        protected ?Competition $competition = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::beginTransaction();

        try {
            // Step 1: Determine the competitions to process
            $competitions = $this->getCompetitionsToProcess();

            // Step 2: Process each competition
            foreach ($competitions as $competition) {
                $this->processCompetition($competition);
            }

            // Step 3: Remove auditor from competitions
            if ($this->competition !== null) {
                // Remove from specific competition
                $this->competition->auditors()->detach($this->auditorId);
            } else {
                // Remove from all upcoming and running competitions
                $this->removeAuditorFromCompetitions();
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete auditor', [
                'auditor_id' => $this->auditorId,
                'competition_id' => $this->competition?->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get the competition.
     */
    public function getCompetition()
    {
        return $this->competition;
    }   

    /**
     * Get the auditor ID.
     */
    public function getAuditorId()
    {
        return $this->auditorId;
    }
    /**
     * Get the competitions that need to be processed.
     */
    protected function getCompetitionsToProcess()
    {
        if ($this->competition !== null) {
            return collect([$this->competition]);
        }

        return Competition::whereHas('auditors', function ($query) {
            $query->where('admins.id', $this->auditorId);
        })->where('status', '1')->get();
    }

    /**
     * Process a single competition.
     */
    protected function processCompetition(Competition $competition): void
    {
        // Get all auditors in the competition except the one being deleted
        $auditors = DB::table('admin_competition')
            ->where('competition_id', $competition->id)
            ->where('admin_id', '!=', $this->auditorId)
            ->pluck('admin_id')
            ->toArray();

        if (empty($auditors)) {
            return;
        }

        // Select a random auditor from the remaining auditors
        $randomAuditorId = $auditors[array_rand($auditors)];

        // Update all records in level_admin_user with the selected random auditor
        DB::table('level_admin_user')
            ->join('levels', 'level_admin_user.level_id', '=', 'levels.id')
            ->where('level_admin_user.admin_id', $this->auditorId)
            ->where('levels.competition_id', $competition->id)
            ->update(['level_admin_user.admin_id' => $randomAuditorId]);
    }

    /**
     * Remove auditor from all upcoming and running competitions.
     */
    protected function removeAuditorFromCompetitions(): void
    {
        DB::table('admin_competition')
            ->join('competitions', 'competitions.id', '=', 'admin_competition.competition_id')
            ->where('competitions.status', '!=', '2')
            ->where('admin_competition.admin_id', $this->auditorId)
            ->delete();
    }
} 