<?php

namespace App\Events\Notifications;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class PaymentNotificationEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $notificationData;
    public $notifiableType;

    public function __construct(int $userId, array $notificationData, string $notifiableType)
    {
        $this->userId = $userId;
        $this->notificationData = $notificationData;
        $this->notifiableType = $notifiableType;
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