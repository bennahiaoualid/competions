<?php

namespace App\Jobs\Competition;

use Illuminate\Bus\Queueable;
use App\Models\Competition\Level;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\SystemSettingService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\Monitoring\JobTrackingService;
use App\Jobs\Competition\AIAuditingBatchJobFactory;

class AIAuditingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes timeout
    public $tries = 3; // Retry 3 times

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Level $level
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(
        SystemSettingService $systemSettingService,
        JobTrackingService $jobTrackingService,
        AIAuditingBatchJobFactory $batchJobFactory
        ): void
    {
        try {
            $this->level->load('competition.users', 'questions', 'competition');
            Log::info('Starting AI auditing job for level', [
                'level_id' => $this->level->id,
                'competition_id' => $this->level->competition_id,
                'user_count' => $this->level->competition->users->count()
            ]);

            // Get batch size from system settings
            $batchSize = (int) $systemSettingService->getValue('ai_auditing_max_response_auditing_at_one_batch', 100);
            
            // Load users and questions for this level
            $users = $this->level->competition->users;
            $questions = $this->level->questions;
            $totalUsers = $users->count();
            $totalBatches = ceil($totalUsers / $batchSize);

            Log::info('Dispatching AI auditing batch jobs', [
                'level_id' => $this->level->id,
                'total_users' => $totalUsers,
                'total_questions' => $questions->count(),
                'batch_size' => $batchSize,
                'total_batches' => $totalBatches
            ]);

            // Dispatch batch jobs for each chunk of users
            foreach ($users->chunk($batchSize) as $index => $userBatch) {
                $batchNumber = $index + 1;
                
                Log::info("Dispatching AI auditing batch job {$batchNumber}/{$totalBatches}", [
                    'level_id' => $this->level->id,
                    'batch_number' => $batchNumber,
                    'total_batches' => $totalBatches,
                    'users_in_batch' => $userBatch->count(),
                    'user_ids' => $userBatch->pluck('id')->toArray()
                ]);

                // Create and dispatch batch job using injected factory
                $batchJob = $batchJobFactory->create(
                    level: $this->level, 
                    userBatch: $userBatch, 
                    batchNumber: $batchNumber, 
                    totalBatches: $totalBatches,
                    questions: $questions,
                    userId: $this->level->competition->admin_id
                );
                $jobTrackingService->dispatchWithTracking($batchJob);
            }

            Log::info('AI auditing job completed successfully', [
                'level_id' => $this->level->id,
                'total_batches_dispatched' => $totalBatches
            ]);

        } catch (\Exception $e) {
            Log::error('AI auditing job failed', [
                'level_id' => $this->level->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('AI auditing job failed permanently', [
            'level_id' => $this->level->id,
            'competition_id' => $this->level->competition_id,
            'error' => $exception->getMessage()
        ]);
    }

    /**
     * Get the level being audited.
     */
    public function getLevel(): Level
    {
        return $this->level;
    }
} 