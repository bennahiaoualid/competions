<?php

namespace App\Events\ProcessManagement;

use App\Enums\ProcessTypeEnum;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DelayedProcessCreationEvent
{
    use Dispatchable, SerializesModels;

    public ProcessTypeEnum $processType;
    public string $targetType;
    public int $targetId;
    public ?int $initiatorId;
    public array $contextData;
    public int $checkPeriodHours;

    /**
     * Create a new event instance.
     */
    public function __construct(
        ProcessTypeEnum $processType,
        string $targetType,
        int $targetId,
        ?int $initiatorId = null,
        array $contextData = [],
        int $checkPeriodHours = 24
    ) {
        $this->processType = $processType;
        $this->targetType = $targetType;
        $this->targetId = $targetId;
        $this->initiatorId = $initiatorId;
        $this->contextData = $contextData;
        $this->checkPeriodHours = $checkPeriodHours;
    }
}
