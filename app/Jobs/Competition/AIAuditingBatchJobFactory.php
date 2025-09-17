<?php

namespace App\Jobs\Competition;

use App\Models\User;
use App\Models\Competition\Level;
use Illuminate\Support\Collection;
use App\Models\Competition\Question;
use App\Services\SystemSettingService;
use App\Services\LLM\LLMHandlerFactory;
use App\Services\Competition\AuditService;
use App\Jobs\Competition\AIAuditingBatchJob;
use App\Services\Monitoring\JobTrackingService;
use App\Services\CashManagment\CompetitionCacheManagmentSystem;
use App\Services\Notification\OptimizedCompetitionNotificationService;

class AIAuditingBatchJobFactory
{
    public function __construct(
        private LLMHandlerFactory $llmHandlerFactory,
        private SystemSettingService $systemSettingService,
        private JobTrackingService $jobTrackingService,
        private AuditService $auditService,
        private OptimizedCompetitionNotificationService $notification,
        private CompetitionCacheManagmentSystem $cashService,
    ) {}

    /**
     * Create a new AI auditing batch job instance.
     */
    public function create(
        Level $level,
        Collection $userBatch,
        int $batchNumber,
        int $totalBatches,
        Collection $questions,
        ?int $userId = null
    ): AIAuditingBatchJob {
        return new AIAuditingBatchJob(
            level: $level,
            userBatch: $userBatch,
            batchNumber: $batchNumber,
            totalBatches: $totalBatches,
            questions: $questions,
            llmHandlerFactory: $this->llmHandlerFactory,
            systemSettingService: $this->systemSettingService,
            auditService: $this->auditService,
            notification: $this->notification,
            cashService:$this->cashService,
            userId: $userId,
            skipTrackingCreation: false,
            prompt: null
        );
    }

    /**
     * Create a job instance from tracking payload for retry operations.
     */
    public function createFromPayload(
        array $payload,
        ?int $userId,
        string $trackingId
    ): ?AIAuditingBatchJob {
        // Reconstruct Level from payload
        $level = Level::findOrFail($payload['level_id']);
        
        // Reconstruct User batch from payload
        /** @var Collection */
        $userBatch = User::whereIn('id', $payload['user_ids'])->get();
        
        // Reconstruct Questions from payload
        $questions = Question::whereIn('id', $payload['question_ids'])->get();

        if(!$level || ($userBatch->isEmpty()) || ($questions->isEmpty())) {
            $this->jobTrackingService->deleteJob($trackingId);
            return null;
        }
        
        // Create new job instance with reconstructed data
        $job = new AIAuditingBatchJob(
            level: $level,
            userBatch: $userBatch,
            batchNumber: $payload['batch_number'],
            totalBatches: $payload['total_batches'],
            questions: $questions,
            llmHandlerFactory: $this->llmHandlerFactory,
            systemSettingService: $this->systemSettingService,
            auditService: $this->auditService,
            notification: $this->notification,
            cashService:$this->cashService,
            userId: $userId,
            skipTrackingCreation: true, // Skip creating new tracking record for retry
            prompt: $payload['prompt'] ?? null // Use stored prompt for retry performance
        );
        $job->setTrackingId($trackingId);

        // Restore questionUserCounts for retry validation
        if (isset($payload['question_user_counts'])) {
            $job->setQuestionUserCounts($payload['question_user_counts']);
        }

        // Restore questionUserCounts for retry validation
        if (isset($payload['responses_ids'])) {
            $job->setResponses($payload['responses_ids']);
        }

        return $job;
    }
} 