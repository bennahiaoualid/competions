<?php

namespace App\Contracts\Approval;

use App\Enums\AdminApprovalTypeEnum;

interface ApprovalFactoryInterface
{
    /**
     * Create a handler for the given approval type
     */
    public function createHandler(AdminApprovalTypeEnum $type): ApprovalHandlerInterface;
} 