<?php

namespace App\Notifications\Admin;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use App\Helpers\UrlGenerator;

class OnlyOneAuditorLeftNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $competition;
    public $auditor;
    public $deadline;

    public function __construct($competition, $auditor, $deadline)
    {
        $this->competition = $competition;
        $this->auditor = $auditor;
        $this->deadline = $deadline;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable)
    {
        return [
            // Store only the key and data, NOT translated text
            'translation_key' => 'notifications.auditor_required',
            'translation_data' => [
                'auditor_name' => $this->auditor->name,
                'competition_title' => $this->competition->title,
                'deadline' => $this->deadline->format('Y-m-d H:i:s'),
            ],
            // New notification priority and link fields
            'notification_priority_type' => 'warning', // Since it's about auditor requirement
            'link' => UrlGenerator::url('admin.competitions.edit', $this->competition->id), // Use helper for correct domain
            // Additional metadata (not for translation)
            'competition_id' => $this->competition->id,
            'auditor_id' => $this->auditor->id,
            'deadline' => $this->deadline,
            'type' => 'auditor_required',
        ];
    }

    public function toBroadcast($notifiable)
    {
        // Send pre-translated data for real-time notifications
        $translationKey = 'notifications.auditor_required';
        $translationData = [
            'auditor_name' => $this->auditor->name,
            'competition_title' => $this->competition->title,
            'deadline' => $this->deadline->format('Y-m-d H:i:s'),
        ];
        
        $titleKey = $translationKey . '.title';
        $messageKey = $translationKey . '.message';
        
        $translatedTitle = __($titleKey, $translationData);
        $translatedMessage = __($messageKey, $translationData);
        
        return new BroadcastMessage([
            'id' => $this->id,
            'title' => $translatedTitle,
            'message' => $translatedMessage,
            'notification_priority_type' => 'warning',
            'link' => route('admin.competitions.edit', base64_encode($this->competition->id)), // Use helper for correct domain
            'link_text' => __('notifications.link_text.detail'),
            'read_at' => null,
            'created_at' => now()->toISOString(),
        ]);
    }
} 