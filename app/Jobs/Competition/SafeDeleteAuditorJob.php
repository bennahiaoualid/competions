<?php

namespace App\Jobs\Competition;

use App\Models\Admin\Admin;
use Illuminate\Support\Facades\DB;
use App\Jobs\Base\BaseTrackableJob;
use Illuminate\Support\Facades\Log;
use App\Models\Tracking\JobTracking;
use App\Models\Competition\Competition;

class SafeDeleteAuditorJob extends BaseTrackableJob
{
    private int $auditorId;
    private ?Competition $competition;

    public function __construct(int $auditorId, ?Competition $competition = null, ?int $userId = null)
    {
        $this->auditorId = $auditorId;
        $this->competition = $competition;

        parent::__construct(
            userId: $userId,
            entityType: 'Admin',
            entityId: $auditorId
        );
    }

    protected function executeJob(): array
    {
        return DB::transaction(function () {
            throw new \Exception("Force job failure for testing purposes");

            $competitions = $this->getCompetitionsToProcess();

            foreach ($competitions as $competition) {
                $this->processCompetition($competition);
            }

            if ($this->competition !== null) {
                $this->competition->auditors()->detach($this->auditorId);
            } else {
                $this->removeAuditorFromCompetitions();
            }

            return [
                'auditor_id' => $this->auditorId,
                'competition_id' => $this->competition?->id,
                'completed_at' => now(),
            ];
        });
    }

    protected function getPayloadData(): array
    {
        return [
            'auditor_id' => $this->auditorId,
            'competition_id' => $this->competition?->id,
            'action' => 'delete_auditor',
        ];
    }

    protected function onFinalFailure(\Throwable $e, JobTracking $tracking)
    {
        Log::warning("DeleteAuditorJob failed", [
            'auditor_id' => $this->auditorId,
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
            $query->where('admins.id', $this->auditorId);
        })->where('status', '1')->get();
    }

    private function processCompetition(Competition $competition): void
    {
        $auditors = DB::table('admin_competition')
            ->where('competition_id', $competition->id)
            ->where('admin_id', '!=', $this->auditorId)
            ->pluck('admin_id')
            ->toArray();

        if (empty($auditors)) {
            return;
        }

        $randomAuditorId = $auditors[array_rand($auditors)];

        DB::table('level_admin_user')
            ->join('levels', 'level_admin_user.level_id', '=', 'levels.id')
            ->where('level_admin_user.admin_id', $this->auditorId)
            ->where('levels.competition_id', $competition->id)
            ->update(['level_admin_user.admin_id' => $randomAuditorId]);
    }

    private function removeAuditorFromCompetitions(): void
    {
        DB::table('admin_competition')
            ->join('competitions', 'competitions.id', '=', 'admin_competition.competition_id')
            ->where('competitions.status', '!=', '2')
            ->where('admin_competition.admin_id', $this->auditorId)
            ->delete();
    }

    public static function fromTrackingPayload(array $payload, ?int $userId, string $trackingId): static
    {
        $job = new static($payload['admin_id'], $userId);
        $job->trackingId = $trackingId;
        return $job;
    }

    protected function getCustomMessage(): array
    {
        // get the target admin
        $admin = Admin::select('name')->find($this->auditorId) ?? '';

        // check if we deleting an auditor from a competition or full admin
        $type = $this->competition ? 'auditor' : 'admin';

        return [
            'success' =>[
                __('messages.job.completed'),
                __('messages.job.' . $type . '_deleted', ['admin' => $admin]),
            ],
            'error' =>[
                __('messages.job.failed'),
                __('messages.job.' . $type . '_delete_failed', ['admin' => $admin]),
            ],
        ];
    }
}
