<?php

namespace App\Listeners\ProcessManagement;

use App\Events\ProcessManagement\DelayedProcessCreationEvent;
use App\Services\ProcessManagement\DelayedProcessService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class DelayedProcessCreationListener implements ShouldQueue
{
    use InteractsWithQueue;

    private DelayedProcessService $delayedProcessService;

    /**
     * Create the event listener.
     */
    public function __construct(DelayedProcessService $delayedProcessService)
    {
        $this->delayedProcessService = $delayedProcessService;
    }

    /**
     * Handle the event.
     */
    public function handle(DelayedProcessCreationEvent $event): void
    {
        try {
            $process = $this->delayedProcessService->storeDelayedProcess(
                processType: $event->processType,
                targetType: $event->targetType,
                targetId: $event->targetId,
                initiatorId: $event->initiatorId,
                contextData: $event->contextData,
                checkPeriodHours: $event->checkPeriodHours
            );

            Log::info('Delayed process created via event', [
                'process_id' => $process->id,
                'process_type' => $event->processType->value,
                'target_type' => $event->targetType,
                'initiator_id' => $event->initiatorId,
                'target_id' => $event->targetId,
                'check_period_hours' => $event->checkPeriodHours,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create delayed process via event', [
                'process_type' => $event->processType->value,
                'target_type' => $event->targetType,
                'target_id' => $event->targetId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
