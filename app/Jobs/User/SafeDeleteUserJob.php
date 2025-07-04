<?php

namespace App\Jobs\User;

use Throwable;
use App\Models\User;
use App\Models\Admin\Admin;
use App\Events\Monitoring\DeletionRequested;
use Illuminate\Support\Facades\DB;
use App\Jobs\Base\BaseTrackableJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Monitoring\JobTracking;
use App\Models\Competition\Competition;

class SafeDeleteUserJob extends BaseTrackableJob
{
    private User $user;
    private Admin $initiator;
    private string $reason;

    public function __construct(
        User $user,
        ?Admin $initiator = null,
        string $reason,
        bool $skipTrackingCreation = false
    ) {
        $this->user = $user;
        $this->initiator = $initiator;
        $this->reason = $reason;
        
        parent::__construct(
            userId: $initiator->id,
            entityType: 'User',
            entityId: $user->id,
            jobType: 'user_deletion',
            skipTrackingCreation: $skipTrackingCreation
        );
    }

    protected function executeJob(): array
    {
        return DB::transaction(function () {
            if(!$this->initiator) {
                throw new \Exception('the admin who requested the deletion is not found');
            }
            
            $this->deleteLevelAdminUserRelations();
            $this->deleteCompetitionUserRelations();

            event(new DeletionRequested(
                $this->user, 
                $this->initiator, 
                $this->reason, 
                $this->user->name.' - '.$this->user->email
            ));
            $this->user->delete();

            return $this->getResultValues();
        });
    }

    protected function getPayloadData(): array
    {
        return [
            'user_id' => $this->user->id,
            'action' => 'soft_delete_user',
            'reason' => $this->reason,
            'initiator_id' => $this->initiator->id,
        ];
    }

    protected function onFinalFailure(Throwable $e, JobTracking $tracking): void
    {
        if ($this->user->trashed()) {
            $this->user->restore(); // undo soft delete
        }

        $this->updateJobStatus($tracking, [
            'status' => 'failed',
            'error_message' => $e->getMessage(),
            'result' => $this->getResultValues(),
            'failed_at' => now(),
        ], $this->getCustomMessage()['error']);

        Log::error("SafeDeleteUserJob failed", [
            'user_id' => $this->user->id,
            'error' => $e->getMessage()
        ]);
    }

    protected function getCustomMessage(): array
    {
        return [
            'success' => [
                __('job.messages.completed'),
                __('job.messages.user_deleted', ['user' => $this->user->name]),
            ],
            'error' => [
                __('job.messages.failed'),
                __('job.messages.user_delete_failed', ['user' => $this->user->name]),
                __('job.messages.user_restored', ['user' => $this->user->name]),
            ],
        ];
    }

    private function deleteLevelAdminUserRelations(): void
    {
        $competitionIds = Competition::whereHas('users', fn ($q) =>
            $q->where('users.id', $this->user->id)
        )->where('status', '1')->pluck('id');

        if ($competitionIds->isEmpty()) return;

        DB::table('level_admin_user')
            ->join('levels', 'level_admin_user.level_id', '=', 'levels.id')
            ->where('level_admin_user.user_id', $this->user->id)
            ->whereIn('levels.competition_id', $competitionIds)
            ->delete();
    }

    private function deleteCompetitionUserRelations(): void
    {
        DB::table('competition_user')
            ->join('competitions', 'competitions.id', '=', 'competition_user.competition_id')
            ->where('competitions.status', '!=', '2')
            ->where('competition_user.user_id', $this->user->id)
            ->delete();
    }

    private function getResultValues(): array
    {
        return [
            'user_id' => $this->user->id,
            'user' => $this->user->name,
            'completed_at' => now(),
        ];
    }

    public static function fromTrackingPayload(array $payload, ?int $initiatorId, string $trackingId): ?static
    {
        $user = User::withTrashed()->find($payload['user_id']);
        $initiator = Admin::find($payload['initiator_id']);
        if (!$user) {
            Log::warning("SafeDeleteUserJob retrying failed: user not found", [
                'user_id' => $payload['user_id'],
            ]);
            return null;
        }
        
        $job = new static($user, $initiator, $payload['reason'], skipTrackingCreation: true);
        $job->trackingId = $trackingId;
        return $job;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
