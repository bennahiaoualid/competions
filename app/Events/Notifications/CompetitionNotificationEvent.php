<?php

namespace App\Events\Notifications;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class CompetitionNotificationEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The user ID to broadcast to.
     */
    public int $userId;

    /**
     * The notification data.
     */
    public array $notificationData;

    /**
     * The notifiable type.
     */
    public string $notifiableType;

    /**
     * Create a new event instance.
     */
    public function __construct(int $userId, array $notificationData, string $notifiableType = User::class)
    {
        $this->userId = $userId;
        $this->notifiableType = $notifiableType;
        $this->notificationData = $notificationData;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        if($this->notifiableType === User::class){
            return [
                new PrivateChannel('notification.user.' . $this->userId),
            ];
        }else{
            return [
                new PrivateChannel('notification.admin.' . $this->userId),
            ];
        }
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return $this->notificationData;
    }

    /**
     * Get the broadcast event name.
     */
    public function broadcastAs(): string
    {
        return 'notification.received';
    }
} 