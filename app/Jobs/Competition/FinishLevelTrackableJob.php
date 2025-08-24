<?php

namespace App\Jobs\Competition;

use Cache;
use Throwable;
use App\Enums\JobTypeEnum;
use App\Helpers\UserNotifyEmail;
use App\Models\Competition\Level;
use Illuminate\Support\Facades\DB;
use App\Contracts\FlasherInterface;
use App\Jobs\Base\BaseTrackableJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Monitoring\JobTracking;
use App\Services\Monitoring\JobTrackingService;
use App\Interface\Competition\LevelRepositoryInterface;

class FinishLevelTrackableJob extends BaseTrackableJob
{
    private Level $level;
    private FlasherInterface $flasher;
    private LevelRepositoryInterface $levelRepository;

    public function __construct(
        Level $level,
        ?int $userId = null,
        bool $skipTrackingCreation = false,

    ) {
        $this->level = $level;
        
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
        $this->levelRepository = app(LevelRepositoryInterface::class);
        $level = $this->level->load('competition.users', 'competition.auditors');

        DB::transaction(function () use ($level) {
            $this->levelRepository->insertMissingResponsesForLevel($level);
            $this->assignUsersToAuditors($this->levelRepository);

            $updated = $this->levelRepository->update($level, ['status' => "2"]);

            if ($updated) {
                UserNotifyEmail::auditorsFinishLevel($level->competition, $level);
            }
            
        });

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

        Log::error('FinishLevelTrackableJob failed: ' . $e->getMessage(), [
            'level_id' => $this->level->id,
            'competition_id' => $this->level->competition_id,
            'exception' => $e,
        ]);
    }

    public static function fromTrackingPayload(array $payload, ?int $userId, string $trackingId): ?static
    {
        $level = Level::find($payload['level_id']);
        
        if (!$level || $level->status == Level::STATUS_FINISHED) {
            Log::warning("FinishLevelTrackableJob retrying failed: level not found", [
                'payload' => $payload,
                'tracking_id' => $trackingId,
            ]);
            $service = app(JobTrackingService::class);
            $service->deleteJob($trackingId);
            return null;
        }

        $job = new static($level, $userId, skipTrackingCreation: true);
        $job->trackingId = $trackingId;
        return $job;
    }

    protected function assignUsersToAuditors(LevelRepositoryInterface $levelRepository): void
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

    public function getLevelRepository(): LevelRepositoryInterface
    {
        return $this->levelRepository;
    }

    public function getFlasher(): FlasherInterface
    {
        return $this->flasher;
    }
} 