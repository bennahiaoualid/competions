<?php

namespace App\Jobs\Admin;

use App\Models\Admin\Admin;
use App\Enums\ProcessTypeEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Competition\Competition;
use Illuminate\Foundation\Queue\Queueable;
use App\Exceptions\StopJobRetriesException;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\ProcessManagement\DelayedProcess;
use App\Events\Notifications\Admin\AuditorDeletionFailed;
use App\Services\ProcessManagement\DelayedProcessService;
use App\Events\ProcessManagement\DelayedProcessCreationEvent;

/**
 * Safely removes an admin from auditor roles in competitions.
 * 
 * This job handles the safe removal of an admin from auditor positions in competitions.
 * The process includes:
 * - Checking if there's a delayed process that allows bypass
 * - Validating that the admin is not the only auditor in active competitions
 * - Creating delayed process if admin is only auditor
 * - Reassigning level-admin-user relationships to other auditors
 * - Removing the admin from competition auditor relationships
 * 
 * @package App\Jobs\Admin
 */
class SafeDeleteAuditorJob implements ShouldQueue
{
    use Queueable;
    private Admin $auditor;
    private DelayedProcessService $delayedProcessService;
    private ?DelayedProcess $existingDelayedProcess = null;
    private ?int $initiatorId;

    /**
     * Create a new safe delete auditor job instance.
     *
     * @param Admin $auditor The admin to be removed from auditor roles
     * @param int|null $initiatorId The ID of the admin initiating the deletion
     */
    public function __construct(Admin $auditor, ?int $initiatorId = null)
    {
        $this->auditor = $auditor;
        $this->initiatorId = $initiatorId ?? Auth::id();
        $this->delayedProcessService = app(DelayedProcessService::class);
    }

    /**
     * Execute the safe delete auditor job.
     *
     * This method performs the complete auditor removal process:
     * 1. Checks if there's a delayed process that allows bypass
     * 2. Gets all competitions where the admin is an auditor
     * 3. Validates that the admin is not the only auditor in active competitions
     * 4. Creates delayed process if admin is only auditor
     * 5. Processes each competition to reassign level-admin-user relationships
     * 6. Removes the admin from competition auditor relationships
     *
     * @return void
     * @throws StopJobRetriesException If the admin is the only auditor in active competitions
     * @throws \Exception If any step in the process fails
     */
    public function handle(): void
    {
        // Load existing delayed process once
        $this->loadExistingDelayedProcess();

        // FIRST: Check if there's a delayed process that allows bypass
        if ($this->shouldBypassOnlyAuditorCheck()) {
            Log::info('Bypassing only auditor check due to delayed process', [
                'auditor_id' => $this->auditor->id,
                'auditor_name' => $this->auditor->name,
            ]);
            
            $competitions = $this->getCompetitionsToProcess();
            
            // Delete competitions where auditor is the only auditor
            $this->deleteOnlyAuditorCompetitions($competitions);
            
            // Process remaining competitions normally
            $this->processCompetitions($competitions);
            $this->removeAuditorFromCompetitions();
            $this->cleanupDelayedProcess();
            return;
        }

        // SECOND: Normal business logic
        $competitions = $this->getCompetitionsToProcess();
        $onlyAuditorCompetitions = $this->findOnlyAuditorCompetitions($competitions);
        
        if ($onlyAuditorCompetitions->count() > 0) {
            $this->handleOnlyAuditorScenario($onlyAuditorCompetitions);
            return;
        }

        // THIRD: Normal processing
        $this->processCompetitions($competitions);
        $this->removeAuditorFromCompetitions();
    }

    /**
     * Load existing delayed process for this auditor
     */
    private function loadExistingDelayedProcess(): void
    {
        $this->existingDelayedProcess = $this->delayedProcessService->findExistingProcess(
            ProcessTypeEnum::DELETE_AUDITOR,
            'Admin',
            $this->auditor->id
        );
    }

    /**
     * Check if we should bypass the only auditor check due to delayed process
     */
    private function shouldBypassOnlyAuditorCheck(): bool
    {
        return $this->existingDelayedProcess && $this->existingDelayedProcess->isReadyForRetry();
    }

    /**
     * Find competitions where the admin is the only auditor
     */
    private function findOnlyAuditorCompetitions($competitions)
    {
        return $competitions->filter(function ($competition) {
            return $competition->status === Competition::STATUS_ACTIVE &&
                $competition->auditors->count() === 1 &&
                $competition->auditors->first()->id === $this->auditor->id;
        });
    }

    /**
     * Handle the scenario where admin is the only auditor in some competitions
     */
    private function handleOnlyAuditorScenario($onlyAuditorCompetitions): void
    {
        $onlyAuditorCompetitions = $onlyAuditorCompetitions->unique('id')->values();

        // Send notifications to competition owners
        $this->sendNotifications($onlyAuditorCompetitions);

        // Create delayed process
        $this->createDelayedProcess($onlyAuditorCompetitions);

        throw new StopJobRetriesException(
            translationKey: 'job.errors.only_auditor',
            contextData: ['competitions' => $onlyAuditorCompetitions->pluck('title')->toArray()],
            message: 'Cannot delete admin: they are the only auditor in some competitions.'
        );
    }

    /**
     * Send notifications to competition owners
     */
    private function sendNotifications($competitions): void
    {
        foreach ($competitions as $competition) {
            event(new AuditorDeletionFailed(
                $competition,
                $this->auditor,
                $competition->admin,
                now()->addDay()
            ));
        }
    }

    /**
     * Create delayed process for this auditor
     */
    private function createDelayedProcess($competitions): void
    {        
        event(new DelayedProcessCreationEvent(
            processType: ProcessTypeEnum::DELETE_AUDITOR,
            targetType: 'Admin',
            targetId: $this->auditor->id,
            initiatorId: $this->initiatorId,
            contextData: [
                'competition_ids' => $competitions->pluck('id')->toArray(),
                'competition_titles' => $competitions->pluck('title')->toArray(),
                'total_competitions' => $competitions->count(),
                'created_at' => now()->toISOString(),
            ],
            checkPeriodHours: 24
        ));

        Log::info('Fired delayed process creation event', [
            'auditor_id' => $this->auditor->id,
            'auditor_name' => $this->auditor->name,
            'init' => $this->initiatorId,
            'competition_count' => $competitions->count(),
            'competition_titles' => $competitions->pluck('title')->toArray(),
        ]);
    }

    /**
     * Clean up delayed process after successful execution
     */
    private function cleanupDelayedProcess(): void
    {
        if ($this->existingDelayedProcess) {
            $this->delayedProcessService->deleteProcess($this->existingDelayedProcess);
            
            Log::info('Cleaned up delayed process after successful auditor deletion', [
                'auditor_id' => $this->auditor->id,
                'auditor_name' => $this->auditor->name,
            ]);
        }
    }

    /**
     * Process all competitions
     */
    private function processCompetitions($competitions): void
    {
        foreach ($competitions as $competition) {
            $this->processCompetition($competition);
        }
    }

    /**
     * Get all competitions where the admin is an auditor.
     *
     * This method retrieves all competitions where the admin serves as an auditor
     * and filters for only active and pending competitions, as these are the
     * ones that need processing during the auditor removal process.
     *
     * @return \Illuminate\Support\Collection Collection of competitions where the admin is an auditor
     */
    private function getCompetitionsToProcess()
    {
        return Competition::with('admin')->whereHas('auditors', function ($query) {
            $query->where('admins.id', $this->auditor->id);
        })->whereIn('status', [Competition::STATUS_ACTIVE, Competition::STATUS_PENDING])->get();
    }

    /**
     * Process a single competition to reassign level-admin-user relationships.
     *
     * This method handles the reassignment of level-admin-user relationships
     * for a specific competition. It finds other auditors in the competition
     * and randomly assigns the level-admin-user relationships to one of them.
     *
     * @param Competition $competition The competition to process
     * @return void
     */
    private function processCompetition(Competition $competition): void
    {
        $auditors = DB::table('admin_competition')
            ->where('competition_id', $competition->id)
            ->where('admin_id', '!=', $this->auditor->id)
            ->pluck('admin_id')
            ->toArray();

        if (empty($auditors)) {
            return;
        }

        $randomAuditorId = $auditors[array_rand($auditors)];

        DB::table('level_admin_user')
            ->join('levels', 'level_admin_user.level_id', '=', 'levels.id')
            ->where('level_admin_user.admin_id', $this->auditor->id)
            ->where('levels.competition_id', $competition->id)
            ->update(['level_admin_user.admin_id' => $randomAuditorId]);
    }

    /**
     * Remove the admin from all competition auditor relationships.
     *
     * This method removes the admin from the admin_competition pivot table
     * for all active and pending competitions, effectively removing them
     * from their auditor roles.
     *
     * @return void
     */
    private function removeAuditorFromCompetitions(): void
    {
        DB::table('admin_competition')
            ->join('competitions', 'competitions.id', '=', 'admin_competition.competition_id')
            ->whereIn('competitions.status', [Competition::STATUS_ACTIVE, Competition::STATUS_PENDING])
            ->where('admin_competition.admin_id', $this->auditor->id)
            ->delete();
    }

    /**
     * Delete competitions where the auditor is the only auditor
     */
    private function deleteOnlyAuditorCompetitions($competitions): void
    {
        $onlyAuditorCompetitions = $this->findOnlyAuditorCompetitions($competitions);
        
        if ($onlyAuditorCompetitions->count() > 0) {
            Log::warning('Deleting competitions where auditor is the only auditor due to delayed process', [
                'auditor_id' => $this->auditor->id,
                'auditor_name' => $this->auditor->name,
                'competition_count' => $onlyAuditorCompetitions->count(),
                'competition_titles' => $onlyAuditorCompetitions->pluck('title')->toArray(),
            ]);

            // Bulk delete competitions - foreign key constraints will handle related records
            $competitionIds = $onlyAuditorCompetitions->pluck('id')->toArray();
            Competition::whereIn('id', $competitionIds)->delete();

            Log::info('Bulk deleted competitions due to delayed process', [
                'competition_ids' => $competitionIds,
                'competition_count' => count($competitionIds),
                'auditor_id' => $this->auditor->id,
                'auditor_name' => $this->auditor->name,
            ]);
        }
    }
}
