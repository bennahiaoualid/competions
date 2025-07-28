<?php

namespace App\Services\Notification;

use App\Models\Competition\Competition;
use App\Models\Competition\Level;
use App\Models\User;
use App\Jobs\Notifications\BatchCompetitionNotificationJob;
use App\Jobs\Notifications\BatchBroadcastNotificationJob;
use Illuminate\Support\Collection;

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

        // Dispatch single batch job for database notifications
        BatchCompetitionNotificationJob::dispatch($userIds, $notificationData);
        
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
            'updated',
            'activated',
            'level_activated',
            'level_finished'
        ]);
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
        return User::eligibleForCompetition($competition->age_start, $competition->age_end, $competition->id)->get();
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
                return 'info';
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
            default:
                return route('competitions.detail', $competition);
        }
    }
} 