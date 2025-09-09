<?php

namespace App\Jobs\Competition;

use Cache;
use Throwable;
use App\Enums\JobTypeEnum;
use App\Helpers\UserNotifyEmail;
use App\Models\Competition\Level;
use Illuminate\Support\Facades\DB;
use App\Jobs\Base\BaseTrackableJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Monitoring\JobTracking;
use App\Interface\Competition\LevelRepositoryInterface;
use App\Jobs\Competition\FinishLevelTrackableJobFactory;
use App\Services\Notification\OptimizedCompetitionNotificationService;
use App\Jobs\Competition\AIAuditingJob;
use App\Exceptions\StopJobRetriesException;

class FinishLevelTrackableJob extends BaseTrackableJob
{
    private Level $level;
    private LevelRepositoryInterface $levelRepository;
    private OptimizedCompetitionNotificationService $notificationService;

    public function __construct(
        Level $level,
        LevelRepositoryInterface $levelRepository,
        OptimizedCompetitionNotificationService $notificationService,
        ?int $userId = null,
        bool $skipTrackingCreation = false,
    ) {
        $this->level = $level;
        $this->levelRepository = $levelRepository;
        $this->notificationService = $notificationService;
        
        parent::__construct(
            userId: $userId,
            entityType: 'Level',
            entityId: $level->id,
            jobType: JobTypeEnum::FINISH_LEVEL->value,
            skipTrackingCreation: $skipTrackingCreation
        );
    }

    protected function executeJob(): array
    {
        // Lock and re-check to guarantee single execution
        DB::transaction(function () {
            $locked = Level::where('id', $this->level->id)->lockForUpdate()->first();
            if (!$locked) {
                throw new StopJobRetriesException('Level not found');
            }
            if ($locked->finish_job_running !== true) {
                // Ensure flag is set under lock if not already
                $locked->update(['finish_job_running' => true]);
            }
        });

        $level = $this->level->load('competition.users', 'competition.auditors');

        DB::transaction(function () use ($level) {
            // Core level finishing operations
            $this->levelRepository->insertMissingResponsesForLevel($level);
            $this->assignAuditorsToUsersInPivot($this->levelRepository);

            $updated = $this->levelRepository->update($level, [
                'status' => Level::STATUS_FINISHED,
                'finished_at' => now()
            ]);
            
            if ($updated) {
                UserNotifyEmail::auditorsFinishLevel($level->competition, $level);
                // Send notification to competition users
                $this->notificationService->levelFinished($level->competition, $level);
            }
        });

        // AFTER level is successfully finished, optionally dispatch AI auditing
        if ($level->competition->ai_auditing) {
            Log::info('Dispatching AI auditing job for level', [
                'level_id' => $level->id,
                'competition_id' => $level->competition_id,
                'user_count' => $level->competition->users->count()
            ]);
            
            dispatch(new AIAuditingJob($level));
        }

        // Reset the running flag on success
        $this->safeResetRunningFlag();

        return $this->getResultValues();
    }

    protected function getPayloadData(): array
    {
        return [
            'level_id' => $this->level->id,
        ];
    }

    protected function getCustomMessage(): array
    {
        return [
            'success' => [
                __('job.messages.completed'),
                __('job.messages.level_finished', [
                    'level' => $this->level->name
                ]),
            ],
            'error' => [
                __('job.messages.failed'),
                __('job.messages.level_finish_failed', [
                    'level' => $this->level->name
                ]),
            ],
        ];
    }

    protected function onFinalFailure(Throwable $e, JobTracking $tracking): void
    {        
        $this->updateJobStatus($tracking, [
            'status' => 'failed',
            'error_message' => $e->getMessage(),
            'result' => $this->getResultValues(false),
            'failed_at' => now(),
        ], $this->getCustomMessage()['error']);

        // Reset the running flag on failure as well
        $this->safeResetRunningFlag();

        Log::error('FinishLevelTrackableJob failed: ' . $e->getMessage(), [
            'level_id' => $this->level->id,
            'competition_id' => $this->level->competition_id,
            'exception' => $e,
        ]);
    }

    public static function fromTrackingPayload(array $payload, ?int $userId, string $trackingId): ?static
    {
        // Create factory directly without service container
        $factory = new FinishLevelTrackableJobFactory(
            new \App\Repository\Competition\LevelRepository(),
            new \App\Services\Notification\OptimizedCompetitionNotificationService(),
            new \App\Services\Monitoring\JobTrackingService()
        );
        
        return $factory->createFromPayload($payload, $userId, $trackingId);
    }

    protected function assignAuditorsToUsersInPivot(LevelRepositoryInterface $levelRepository): void
    {
        $users = $this->level->competition->users;
        $auditors = $this->level->competition->auditors;

        $levelRepository->assignAuditorsToUsersInPivot($this->level, $users, $auditors);
        Cache::forget('assigned_user_count_admin_' . Auth::id());
    }

    private function getResultValues(): array
    {
        return [
            'level_id' => $this->level->id,
            'level_name' => $this->level->name,
            'competition' => $this->level->competition->title ?? 'Unknown',
            'completed_at' => now(),
        ];
    }

    public function getLevel(): Level
    {
        return $this->level;
    }

    private function safeResetRunningFlag(): void
    {
        try {
            Level::where('id', $this->level->id)->update(['finish_job_running' => false]);
        } catch (\Throwable $e) {
            Log::warning('Failed to reset finish_job_running flag', [
                'level_id' => $this->level->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}