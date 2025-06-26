<?php

namespace App\Jobs\Competition;

use App\Models\Admin\Admin;
use Illuminate\Support\Facades\DB;
use App\Jobs\Base\BaseTrackableJob;
use Illuminate\Support\Facades\Log;
use App\Models\Monitoring\JobTracking;
use App\Models\Competition\Competition;

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

    protected function executeJob(): array
    {
        return DB::transaction(function () {


            // check if the admin is still exists before starting the job
            if (!Admin::where('id', $this->auditor->id)->exists() && $this->jobType === 'admin') {
                throw new \RuntimeException("Cannot retry: Admin already deleted.");
            }

            $competitions = $this->getCompetitionsToProcess();

            foreach ($competitions as $competition) {
                $this->processCompetition($competition);
            }

            if ($this->competition !== null) {
                $this->competition->auditors()->detach($this->auditor->id);
            } else {
                $this->removeAuditorFromCompetitions();
            }

            return [
                'auditor_id' => $this->auditor->id,
                'competition_id' => $this->competition?->id,
                'completed_at' => now(),
            ];
        });
    }

    protected function getPayloadData(): array
    {
        return [
            'auditor_id' => $this->auditor->id,
            'competition_id' => $this->competition?->id,
            'action' => 'delete_auditor',
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

    public static function fromTrackingPayload(array $payload, ?int $userId, string $trackingId): static
    {
        $auditor = Admin::find($payload['auditor_id']);
        $competition = $payload['competition_id'] ? Competition::find($payload['competition_id']) : null;
    
        $job = new static($auditor, $competition, $userId, skipTrackingCreation: true);
        $job->trackingId = $trackingId;
        return $job;
    }
    

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
}
