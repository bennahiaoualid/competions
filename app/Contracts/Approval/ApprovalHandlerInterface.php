<?php

namespace App\Contracts\Approval;

use App\Models\Admin\AdminApproval;
use App\Enums\AdminApprovalTypeEnum;

interface ApprovalHandlerInterface
{
    /**
     * Handle the approval request
     */
    public function handle(AdminApproval $approval): bool;

    /**
     * Check if this handler can handle the given approval type
     */
    public function canHandle(AdminApprovalTypeEnum $type): bool;
} 