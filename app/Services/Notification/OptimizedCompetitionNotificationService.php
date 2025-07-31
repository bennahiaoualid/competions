<?php

namespace App\Services\Notification;

use App\Models\User;
use App\Models\Competition\Level;
use App\Models\Admin\Admin;
use App\Models\Admin\AdminApproval;
use Illuminate\Support\Collection;
use App\Models\Competition\Competition;
use App\Jobs\Notifications\BatchBroadcastNotificationJob;
use App\Jobs\Notifications\BatchCompetitionNotificationJob;

class OptimizedCompetitionNotificationService
{
    /**
     * Send batch notification to all users in a competition
     */
    public function notifyCompetitionUsers(Competition $competition, string $eventType, ?Level $level = null, array $additionalData = []): void
    {
        $users = $competition->users;
        
        if ($users->isEmpty()) {
            return;
        }

        $this->dispatchBatchNotifications($users, $competition, $eventType, $level, $additionalData);
    }

    /**
     * Send batch notification to specific users
     */
    public function notifyUsers(Collection $users, Competition $competition, string $eventType, Level $level = null, array $additionalData = []): void
    {
        if ($users->isEmpty()) {
            return;
        }

        $this->dispatchBatchNotifications($users, $competition, $eventType, $level, $additionalData);
    }

    /**
     * Dispatch batch notification jobs instead of individual notifications
     */
    protected function dispatchBatchNotifications(Collection $users, Competition $competition, string $eventType, ?Level $level, array $additionalData): void
    {
        $userIds = $users->pluck('id')->toArray();
        
        // Prepare notification data once
        $notificationData = $this->prepareNotificationData($competition, $eventType, $level, $additionalData);
        
        $notifiableType = $this->getNotifiableType($eventType);
        // Dispatch single batch job for database notifications
        BatchCompetitionNotificationJob::dispatch($userIds, $notificationData, $notifiableType);
        
        // Dispatch single batch job for broadcast notifications (if needed for real-time events)
        if ($this->shouldBroadcast($eventType)) {
            BatchBroadcastNotificationJob::dispatch($userIds, $notificationData);
        }
    }

    /**
     * Prepare notification data structure
     */
    protected function prepareNotificationData(Competition $competition, string $eventType, ?Level $level, array $additionalData): array
    {
        $translationKey = 'notifications.competition.' . $eventType;
        $translationData = $this->getTranslationData($competition, $eventType, $level);

        return [
            'translation_key' => $translationKey,
            'translation_data' => $translationData,
            'notification_priority_type' => $this->getPriorityType($eventType),
            'link' => $this->getNotificationLink($competition, $eventType, $level),
            'competition_id' => $competition->id,
            'level_id' => $level?->id,
            'event_type' => $eventType,
            'type' => 'competition_event',
            'additional_data' => $additionalData,
        ];
    }

    /**
     * Determine if event should be broadcast in real-time
     */
    protected function shouldBroadcast(string $eventType): bool
    {
        return in_array($eventType, [
            'activated',
            'level_activated',
            'level_finished'
        ]);
    }

    /**
     * Determine the type of notifiable
     */
    protected function getNotifiableType(string $eventType): string
    {
        return match($eventType) {
            'auditor_requested' => Admin::class,
            'level_manager_requested' => Admin::class,
            default => User::class,
        };
    }

    /**
     * Competition created event
     */
    public function competitionCreated(Competition $competition): void
    {
        $eligibleUsers = $this->getEligibleUsersForCompetition($competition);
        $this->notifyUsers($eligibleUsers, $competition, 'created');
    }

    /**
     * Competition updated event
     */
    public function competitionUpdated(Competition $competition): void
    {
        $this->notifyCompetitionUsers($competition, 'updated');
    }

    /**
     * Competition activated event
     */
    public function competitionActivated(Competition $competition): void
    {
        $this->notifyCompetitionUsers($competition, 'activated');
    }

    /**
     * Level created event
     */
    public function levelCreated(Competition $competition, Level $level): void
    {
        $this->notifyCompetitionUsers($competition, 'level_created', $level);
    }

    /**
     * Level updated event
     */
    public function levelUpdated(Competition $competition, Level $level): void
    {
        $this->notifyCompetitionUsers($competition, 'level_updated', $level);
    }

    /**
     * Level activated event
     */
    public function levelActivated(Competition $competition, Level $level): void
    {
        $this->notifyCompetitionUsers($competition, 'level_activated', $level);
    }

    /**
     * Level finished event
     */
    public function levelFinished(Competition $competition, Level $level): void
    {
        $this->notifyCompetitionUsers($competition, 'level_finished', $level);
    }

    /**
     * Get users eligible for a competition based on age criteria
     */
    protected function getEligibleUsersForCompetition(Competition $competition): Collection
    {
        return User::eligibleForCompetition($competition->age_start, $competition->age_end)->get();
    }

    /**
     * Get translation data based on event type
     */
    protected function getTranslationData(Competition $competition, string $eventType, ?Level $level): array
    {
        $baseData = [
            'competition_title' => $competition->title,
        ];

        switch ($eventType) {
            case 'level_created':
            case 'level_updated':
            case 'level_finished':
            case 'level_manager_requested':
                return array_merge($baseData, [
                    'level_name' => $level->name,
                ]);
                
            case 'level_activated':
                return array_merge($baseData, [
                    'level_name' => $level->name,
                    'start_time' => $level->start_date->format('Y-m-d H:i'),
                    'duration' => $level->duration . ' minutes',
                ]);
                
            default:
                return $baseData;
        }
    }

    /**
     * Get notification priority type based on event type
     */
    protected function getPriorityType(string $eventType): string
    {
        switch ($eventType) {
            case 'activated':
            case 'level_activated':
                return 'success';
            case 'level_finished':
            case 'user_added':
                return 'info';
            case 'auditor_requested':
            case 'level_manager_requested':
                return 'warning';
            default:
                return 'info';
        }
    }

    /**
     * Get the notification link based on event type
     */
    protected function getNotificationLink(Competition $competition, string $eventType, ?Level $level): string
    {
        switch ($eventType) {
            case 'level_activated':
            case 'level_finished':
            case 'level_created':
            case 'level_updated':
                return route('competitions.level', $level);
            case 'auditor_requested':
            case 'level_manager_requested':
                return route('admin.approvals.index');
            default:
                return route('competitions.detail', $competition);
        }
    }

    /**
     * Notify admin about level manager request
     */
    public function levelManagerRequested(Level $level, Admin $admin): void
    {
        $this->notifyUsers(collect([$admin]), $level->competition, 'level_manager_requested', $level);
    }

    /**
     * Notify multiple admins about auditor request
     */
    public function auditorRequestedBulk(Competition $competition, Collection $admins): void
    {
        $this->notifyUsers($admins, $competition, 'auditor_requested');
    }

    /**
     * Notify competition creator about approval decision
     */
    public function approvalDecision(AdminApproval $approval, string $decision): void
    {
        $competition = $this->getCompetitionFromApproval($approval);
        $admin = $competition->admin;
        $approvedAdmin = $approval->admin;
        
        $notificationData = [
            'translation_key' => "notifications.approval.{$decision}",
            'translation_data' => [
                'competition_title' => $competition->title,
                'admin_name' => $approvedAdmin->name,
                'approval_type' => $approval->type,
            ],
            'notification_priority_type' => $decision === 'approved' ? 'success' : 'warning',
            'link' => $this->getApprovalLink($approval),
            'competition_id' => $competition->id,
            'event_type' => "approval_{$decision}",
            'type' => 'approval_decision',
            'recipient_type' => 'competition_creator',
            'recipient_id' => $competition->admin_id,
        ];

        // Database notification only for approved, broadcast for rejected
        BatchCompetitionNotificationJob::dispatch([$competition->admin_id], $notificationData, Admin::class);
        
        if ($decision === 'rejected') {
            BatchBroadcastNotificationJob::dispatch([$competition->admin_id], $notificationData, Admin::class);
        }
    }

    /**
     * Get competition from approval entity
     */
    private function getCompetitionFromApproval(AdminApproval $approval): Competition
    {
        if ($approval->entity_type === Competition::class) {
            return $approval->entity;
        }
        
        if ($approval->entity_type === Level::class) {
            return $approval->entity->competition;
        }
        
        throw new \InvalidArgumentException('Invalid entity type for approval');
    }

    /**
     * Get appropriate link for approval
     */
    private function getApprovalLink(AdminApproval $approval): string
    {
        return match($approval->entity_type) {
            Competition::class => route('admin.competitions.edit', ['id' => base64_encode($approval->entity_id)]),
            Level::class => route('admin.competitions.level.edit', ['id' => base64_encode($approval->entity_id)]),
            default => route('admin.approvals.index'),
        };
    }
} 