<?php

namespace App\Services\Competition;

use App\Contracts\FlasherInterface;
use App\Contracts\TransactionManagerInterface;
use App\Interface\Competition\AuditRepositoryInterface;
use App\Models\Admin\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuditService
{
    public function __construct(
        protected AuditRepositoryInterface $auditRepository,
        protected TransactionManagerInterface $transactionManager,
        protected FlasherInterface $flasher
    ) {
    }

    /**
     * Get competitions for audit with filtering
     */
    public function auditCompetitions(array $filters = [])
    {        
        return $this->auditRepository->getCompetitionsForAudit($filters);
    }

    /**
     * Get users for a specific level audit
     */
    public function auditUsers(string $levelId): View
    {
        $decodedLevelId = base64_decode($levelId);
        $level = $this->auditRepository->getLevel($decodedLevelId);
        $adminId = Auth::id();
        
        return view("pages.admin.admins.auditor.audited_users", compact('level', 'adminId'));
    }

    /**
     * Get user responses for audit
     */
    public function auditUserResponses(string $levelId, string $userIdentifier): View
    {
        $decodedLevelId = base64_decode($levelId);
        $decodedUserId = base64_decode($userIdentifier);
        
        $level = $this->auditRepository->getLevel($decodedLevelId);
        $user = $this->auditRepository->getUser($decodedUserId);
        
        $questions = $this->auditRepository->getLevelQuestions($decodedLevelId);
        $responses = $this->auditRepository->getUserResponses(
            $decodedUserId,
            array_column($questions, 'id')
        );
        
        return view("pages.admin.admins.auditor.audited_responses", compact('level', 'user', 'questions', 'responses'));
    }

    /**
     * Submit audit scores for user responses
     */
    public function submitAudit(array $responses, int $userId, int $levelId): bool
    {
        return $this->transactionManager->run(function () use ($responses) {
            $result = $this->auditRepository->updateResponseScores($responses);
            $this->flasher->notifyCrudResult($result, "updated");
            return $result;
        });
    }

} 