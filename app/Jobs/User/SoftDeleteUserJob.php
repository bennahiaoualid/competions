<?php

namespace App\Jobs\User;

use Throwable;
use App\Models\User;
use App\Models\Admin\Admin;
use Illuminate\Support\Facades\DB;
use App\Jobs\Base\BaseTrackableJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Monitoring\JobTracking;
use App\Models\Competition\Competition;
use App\Events\Monitoring\DeletionRequested;
use App\Services\Monitoring\JobTrackingService;

class SoftDeleteUserJob extends BaseTrackableJob
{
    private User $user;
    private Admin $initiator;
    private string $reason;
    private ?\Illuminate\Support\Collection $activeCompetitionIds = null;

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
            jobType: 'soft_delete_user',
            skipTrackingCreation: $skipTrackingCreation
        );
    }

    protected function executeJob(): array
    {
        return DB::transaction(function () {
            // Reload the user with a row-level lock
            $user = User::where('id', $this->user->id)->lockForUpdate()->first();

            // Check if already soft deleted
            if ($user->trashed()) {
                // Optionally log or notify
                \Log::info("SoftDeleteUserJob: User {$user->id} already soft deleted.");
                return [
                    'user_id' => $user->id,
                    'user' => $user->name,
                    'already_deleted' => true,
                    'completed_at' => now(),
                ];
            }

            // Use the locked user instance for all further operations
            $this->user = $user;

            // Query once and store
            $this->activeCompetitionIds = $this->getUserCompetitionIdsByStatus(Competition::STATUS_ACTIVE);

            $this->deleteUserRelatedData();
            $this->deleteCompetitionUserRelations();

            $this->user->delete();
            DB::afterCommit(function () {
                event(new DeletionRequested(
                    $this->user, 
                    $this->initiator, 
                    $this->reason, 
                    $this->user->name.' - '.$this->user->email
                ));
            });
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

    /**
     * Get competition IDs for this user by status (single or array of statuses)
     */
    private function getUserCompetitionIdsByStatus(string|array $status): \Illuminate\Support\Collection
    {
        return Competition::whereHas('users', function ($q) {
                $q->where('users.id', $this->user->id);
            })
            ->whereIn('status', (array)$status)
            ->pluck('id');
    }


    private function deleteUserRelatedData(): void
    {
        $competitionIds = $this->activeCompetitionIds;
        if ($competitionIds === null || $competitionIds->isEmpty()) return;

        // Bulk delete all related data in one go
        DB::transaction(function () use ($competitionIds) {
            // Delete level admin user relations
            DB::table('level_admin_user')
                ->join('levels', 'level_admin_user.level_id', '=', 'levels.id')
                ->where('level_admin_user.user_id', $this->user->id)
                ->whereIn('levels.competition_id', $competitionIds)
                ->delete();

            // Delete user responses
            DB::table('responses')
                ->join('questions', 'responses.question_id', '=', 'questions.id')
                ->join('levels', 'questions.level_id', '=', 'levels.id')
                ->where('responses.user_id', $this->user->id)
                ->whereIn('levels.competition_id', $competitionIds)
                ->delete();
        });
    }

    private function deleteCompetitionUserRelations(): void
    {
        // Use cached active IDs and add pending ones
        $activeAndPendingIds = $this->activeCompetitionIds->merge(
            $this->getUserCompetitionIdsByStatus(Competition::STATUS_PENDING)
        );
        
        if ($activeAndPendingIds->isEmpty()) return;

        DB::table('competition_user')
            ->where('user_id', $this->user->id)
            ->whereIn('competition_id', $activeAndPendingIds)
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
        $user = User::find($payload['user_id']);
        $initiator = Admin::find($payload['initiator_id']);
        if (!$user || !$initiator) {
            Log::warning("SafeDeleteUserJob retrying failed: user not found or admin not found", [
                'payload' => $payload,
            ]);
            $service = app(JobTrackingService::class);
            $service->deleteJob($trackingId);
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
