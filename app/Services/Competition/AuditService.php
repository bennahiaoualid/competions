<?php

namespace App\Services\Competition;

use App\Models\User;
use Illuminate\View\View;
use App\Models\Admin\Admin;
use App\Traits\RegisterLogs;
use App\Models\Competition\Level;
use App\Contracts\FlasherInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Traits\UserResponseCalculation;
use App\Contracts\TransactionManagerInterface;
use App\Interface\Competition\AuditRepositoryInterface;

class AuditService
{
    use UserResponseCalculation, RegisterLogs;
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
    public function auditUsersList(Level $level): array
    {
        $admin_id = Auth::id();

        return [
            'level' => $level,
            'admin_id' => $admin_id
        ];
        
    }

    /**
     * Get user responses for audit
     */
    public function auditUserResponses(Level $level, string $userIdentifier): array
    {
        $user = $this->auditRepository->getUser($userIdentifier);
        if(!$user){
            abort(404, "User not found");
        }
        $questions = $this->auditRepository->getLevelQuestionsWithUserResponses($level->id, $user->id);
        
        return [
            'level' => $level,
            'user' => $user,
            'questions' => $questions
        ];
    }

    /**
     * Submit audit scores for user responses
     */
    public function submitAudit(array $responses, Level $level, User $user)
    {
        try {
            if(!$this->auditRepository->isAdminAllowedToAuditUser($level, $user)){
                Log::warning("Unauthorized audit submission attempt", [
                    'admin_id' => Auth::id(),
                    'target_user_id' => $user->id,
                    'level_id' => $level->id,
                    'ip' => request()->ip(),
                    'reason' => 'Admin not authorized to audit this user-level combo',
                ]);
                $this->flasher->notifyCrudResult(false, "error");
                return false;
            }
            $messages = $this->transactionManager->run(function () use ($responses, $level, $user) {
                // get the targeted responses from db
                $responses_origin = $this->auditRepository->getTargetedUserResponses($level->id, $user->id, $responses['scores']);
                return $this->calculateUserResponseFinalScores($responses_origin,$responses['scores']);
            });
            foreach($messages as $message){
                $this->flasher->notify($message[0], $message[1]);
            }
            return true;
        } catch (\Exception $e) {
            $this->registerLogs('Audit Service : SubmitAudit', $e);
            $this->flasher->notifyCrudResult(false, "error");
            return false;
        }
    }

} 