<?php

namespace App\Jobs\Competition;

use App\Models\Admin\Admin;
use Illuminate\Support\Facades\DB;
use App\Jobs\Base\BaseTrackableJob;
use Illuminate\Support\Facades\Log;
use App\Models\Monitoring\JobTracking;
use App\Models\Competition\Competition;
use App\Services\Monitoring\DuplicateJobChecker;

class SafeDeleteAuditorJob extends BaseTrackableJob
{
    private Admin $auditor;
    private ?Competition $competition;

    public function __construct(
        Admin $auditor, 
        ?Competition $competition = null, 
        ?int $userId = null,
        bool $skipTrackingCreation = false
    )
    {
        $this->auditor = $auditor;
        $this->competition = $competition;

        parent::__construct(
            userId: $userId,
            entityType: 'Admin',
            entityId: $auditor->id,
            jobType: $competition ? 'auditor' : 'admin',
            skipTrackingCreation: $skipTrackingCreation
        );
    }

    /**
     * The keys returned here are shown to end-users.
     * ⚠️ Keep translation consistency:
     * If you add/remove keys, update the translation in job.result_keys.
     */
    protected function executeJob(): array
    {
        return DB::transaction(function () {
            //throw new \Exception('test remove auditor');

            $competitions = $this->getCompetitionsToProcess();

            foreach ($competitions as $competition) {
                $this->processCompetition($competition);
            }

            if ($this->competition !== null) {
                $this->competition->auditors()->detach($this->auditor->id);
            } else {
                $this->removeAuditorFromCompetitions();
            }

            $result = $this->getResultValues();

            if ($this->jobType === 'admin') {
                $result['deleted_admin_id'] = $this->auditor->id;
            }

            return $result;
        });
    }

    protected function getPayloadData(): array
    {
        return [
            'auditor_id' => $this->auditor->id,
            'competition_id' => $this->competition?->id,
            'action' => $this->competition ? 'delete_auditor' : 'delete_admin',
        ];
    }

    protected function onFinalFailure(\Throwable $e, JobTracking $tracking)
    {

        if ($this->auditor->trashed()) {
            $this->auditor->restore();
        }

        $this->updateJobStatus($tracking, [
            'status' => 'failed',
            'error_message' => $e->getMessage(),
            'result' => $this->getResultValues(),
            'failed_at' => now(),
        ], $this->getCustomMessage()['error']);

        Log::warning("DeleteAuditorJob failed", [
            'auditor_id' => $this->auditor->id,
            'competition_id' => $this->competition?->id,
            'error' => $e->getMessage()
        ]);
    }

    private function getCompetitionsToProcess()
    {
        if ($this->competition !== null) {
            return collect([$this->competition]);
        }

        return Competition::whereHas('auditors', function ($query) {
            $query->where('admins.id', $this->auditor->id);
        })->where('status', '1')->get();
    }

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

    private function removeAuditorFromCompetitions(): void
    {
        DB::table('admin_competition')
            ->join('competitions', 'competitions.id', '=', 'admin_competition.competition_id')
            ->where('competitions.status', '!=', '2')
            ->where('admin_competition.admin_id', $this->auditor->id)
            ->delete();
    }

    public static function fromTrackingPayload(array $payload, ?int $userId, string $trackingId): ?static
    {
        $auditor = Admin::find($payload['auditor_id']);
        $competition = $payload['competition_id'] ? Competition::find($payload['competition_id']) : null;
        
        if (!$auditor) {
            Log::warning("SafeDeleteAuditorJob retrying failed :: Auditor not found :: fromTrackingPayload", [
                'auditor_id' => $payload['auditor_id'],
                'competition_id' => $payload['competition_id'],
                'error' => "Auditor not found"
            ]);
            return null;
        }

        $job = new static($auditor, $competition, $userId, skipTrackingCreation: true);
        $job->trackingId = $trackingId;
        return $job;
    }

    /**
     * Get the auditor for the job.
     * usualy used in test to get the auditor
     * @return Admin
     */
    public function getAuditor(): Admin
    {
        return $this->auditor;
    }
    

    /**
     * Get the custom message for the job.
     * this messages are sent to job update status event to notify the user if the job status is success or failed
     * @return array
     */
    protected function getCustomMessage(): array
    {
        // check if we are deleting an auditor from a competition or a full admin
        $type = $this->competition ? 'auditor' : 'admin';

        // base messages
        $success = [
            __('job.messages.completed'),
            __('job.messages.' . $type . '_deleted', ['admin' => $this->auditor->name]),
        ];

        $error = [
            __('job.messages.failed'),
            __('job.messages.' . $type . '_delete_failed', ['admin' => $this->auditor->name]),
        ];

        // append extra message if full admin is being deleted
        if ($type === 'admin') {
            $error[] = __('job.messages.admin_restored', ['admin' => $this->auditor->name]);
        }

        return [
                'success' => $success,
                'error' => $error,
            ];
    }

    private function getResultValues(): array
    {
        return [
            'auditor_id' => $this->auditor->id,
            'auditor' => $this->auditor->name,
            'competition_id' => $this->competition?->id,
            'competition' => $this->competition?->title,
            'completed_at' => now(),
        ];
    }
}
