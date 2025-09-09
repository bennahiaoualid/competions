<?php

namespace App\View\Composer;

use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use App\Services\CashManagment\CompetitionCacheManagmentSystem;

class SidebarComposer
{
    protected $competitionCacheManagmentSystem;

    public function __construct(CompetitionCacheManagmentSystem $competitionCacheManagmentSystem)
    {
        $this->competitionCacheManagmentSystem = $competitionCacheManagmentSystem;
    }

    public function compose(View $view)
    {
        $adminId = Auth::id();
        
        // Use injected service
        $userCounts = $this->competitionCacheManagmentSystem->getUsersCountForAdminAuditing($adminId);

        $view->with('needsManualAuditCount', $userCounts['needs_manual_audit']);
        $view->with('needsAIConfirmationCount', $userCounts['needs_ai_confirmation']);
    }
}