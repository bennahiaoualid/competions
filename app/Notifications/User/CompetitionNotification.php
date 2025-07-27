<?php

namespace App\Notifications\User;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use App\Models\Competition\Competition;
use App\Models\Competition\Level;

class CompetitionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $competition;
    public $level;
    public $eventType;
    public $additionalData;

    public function __construct($eventType, Competition $competition, Level $level = null, array $additionalData = [])
    {
        $this->eventType = $eventType;
        $this->competition = $competition;
        $this->level = $level;
        $this->additionalData = $additionalData;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable)
    {
        $translationKey = $this->getTranslationKey();
        $translationData = $this->getTranslationData();

        return [
            // Store only the key and data, NOT translated text
            'translation_key' => $translationKey,
            'translation_data' => $translationData,
            // Notification priority and link fields
            'notification_priority_type' => $this->getPriorityType(),
            'link' => $this->getNotificationLink(),
            // Additional metadata (not for translation)
            'competition_id' => $this->competition->id,
            'level_id' => $this->level?->id,
            'event_type' => $this->eventType,
            'type' => 'competition_event',
        ];
    }

    public function toBroadcast($notifiable)
    {
        $translationKey = $this->getTranslationKey();
        $translationData = $this->getTranslationData();
        
        $titleKey = $translationKey . '.title';
        $messageKey = $translationKey . '.message';
        
        $translatedTitle = __($titleKey, $translationData);
        $translatedMessage = __($messageKey, $translationData);
        
        return new BroadcastMessage([
            'id' => $this->id,
            'title' => $translatedTitle,
            'message' => $translatedMessage,
            'notification_priority_type' => $this->getPriorityType(),
            'link' => $this->getNotificationLink(),
            'read_at' => null,
            'created_at' => now()->toISOString(),
        ]);
    }

    /**
     * Get the translation key based on event type
     */
    protected function getTranslationKey(): string
    {
        return 'notifications.competition.' . $this->eventType;
    }

    /**
     * Get translation data based on event type
     */
    protected function getTranslationData(): array
    {
        $baseData = [
            'competition_title' => $this->competition->title,
        ];

        switch ($this->eventType) {
            case 'created':
                return $baseData;
                
            case 'updated':
                return $baseData;
                
            case 'activated':
                return $baseData;
                
            case 'level_created':
                return array_merge($baseData, [
                    'level_name' => $this->level->name,
                ]);
                
            case 'level_updated':
                return array_merge($baseData, [
                    'level_name' => $this->level->name,
                ]);
                
            case 'level_activated':
                return array_merge($baseData, [
                    'level_name' => $this->level->name,
                    'start_time' => $this->level->start_date->format('Y-m-d H:i:s'),
                    'duration' => $this->level->duration . ' minutes',
                ]);
                
            case 'level_finished':
                return array_merge($baseData, [
                    'level_name' => $this->level->name,
                ]);
                
            default:
                return $baseData;
        }
    }

    /**
     * Get notification priority type based on event type
     */
    protected function getPriorityType(): string
    {
        switch ($this->eventType) {
            case 'activated':
            case 'level_activated':
                return 'success';
                
            case 'level_finished':
                return 'info';
                
            case 'created':
            case 'updated':
            case 'level_created':
            case 'level_updated':
                return 'info';
                
            default:
                return 'info';
        }
    }

    /**
     * Get the notification link based on event type
     */
    protected function getNotificationLink(): string
    {
        switch ($this->eventType) {
            case 'level_activated':
            case 'level_finished':
            case 'level_created':
            case 'level_updated':
                return route('competition.level.detail', [
                    'competition' => base64_encode($this->competition->id),
                    'level' => base64_encode($this->level->id)
                ]);
                
            default:
                return route('competition.detail', base64_encode($this->competition->id));
        }
    }
} 