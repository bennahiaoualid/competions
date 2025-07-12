<?php

namespace App\Services\ProcessManagement;

use App\Enums\ProcessTypeEnum;
use App\Models\ProcessManagement\DelayedProcess;
use Illuminate\Support\Facades\Log;

class DelayedProcessService
{
    /**
     * Store a new delayed process
     */
    public function storeDelayedProcess(
        ProcessTypeEnum $processType,
        string $targetType,
        int $targetId,
        ?int $initiatorId = null,
        array $contextData = [],
        int $checkPeriodHours = 24
    ): DelayedProcess {
        $process = DelayedProcess::updateOrCreate(
            [
                'process_type' => $processType,
                'target_type' => $targetType,
                'target_id' => $targetId,
            ],
            [
                'initiator_id' => $initiatorId,
                'context_data' => $contextData,
                'check_period_hours' => $checkPeriodHours,
            ]
        );

        Log::info('Delayed process created/updated', [
            'process_type' => $processType->value,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'check_period_hours' => $checkPeriodHours,
            'was_created' => $process->wasRecentlyCreated,
            'created_at' => $process->created_at->toISOString(),
            'updated_at' => $process->updated_at->toISOString(),
        ]);

        return $process;
    }

    /**
     * Find existing process for a specific target
     */
    public function findExistingProcess(
        ProcessTypeEnum $processType,
        string $targetType,
        int $targetId
    ): ?DelayedProcess {
        return DelayedProcess::byType($processType)
            ->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->first();
    }

    /**
     * Delete a process (usually after successful execution)
     */
    public function deleteProcess(DelayedProcess $process): bool
    {
        $deleted = $process->delete();

        if ($deleted) {
            Log::info('Delayed process deleted', [
                'process_id' => $process->id,
                'process_type' => $process->process_type->value,
                'target_id' => $process->target_id,
            ]);
        }

        return $deleted;
    }

    /**
     * Clean up expired processes (older than 24 hours)
     */
    public function cleanupExpiredProcesses(): int
    {
        $cutoffDate = now()->subDay(); // 24 hours exactly
        
        $deleted = DelayedProcess::where('created_at', '<', $cutoffDate)->delete();

        Log::info('Cleaned up expired delayed processes', [
            'deleted_count' => $deleted,
            'cutoff_date' => $cutoffDate->toDateString(),
        ]);

        return $deleted;
    }
} 