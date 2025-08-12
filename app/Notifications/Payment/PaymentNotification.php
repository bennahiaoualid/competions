<?php

namespace App\Notifications\Payment;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use App\Models\Payment\PaymentTransaction;
use App\Models\User;
use App\Models\Admin\Admin;

class PaymentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $transaction;
    public $eventType;
    public $additionalData;

    public function __construct(string $eventType, PaymentTransaction $transaction, array $additionalData = [])
    {
        $this->eventType = $eventType;
        $this->transaction = $transaction;
        $this->additionalData = $additionalData;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable)
    {
        return [
            // Translation System (minimal)
            'translation_key' => $this->getTranslationKey(),
            'translation_data' => $this->getMinimalTranslationData(),
            
            // Display & Styling
            'notification_priority_type' => $this->getPriorityType(),
            'link' => $this->getNotificationLink(),
            
            // Metadata (minimal)
            'payment_transaction_id' => $this->transaction->id,
            'event_type' => $this->eventType,
            'type' => 'payment_event'
        ];
    }

    public function toBroadcast($notifiable)
    {
        $translationKey = $this->getTranslationKey();
        $translationData = $this->getMinimalTranslationData();
        
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
        return 'notifications.payment.' . $this->eventType;
    }

    /**
     * Get minimal translation data based on event type
     */
    protected function getMinimalTranslationData(): array
    {
        $payer = $this->transaction->payable;
        
        switch ($this->eventType) {
            case 'transaction_created':
            case 'review_requested':
                // Admin notifications - minimal data
                return [
                    'payer_name' => $payer->name,
                    'payer_type' => $payer instanceof User ? 'user' : 'admin',
                    'amount' => $this->transaction->amount . ' DZD',
                    'transaction_uuid' => $this->transaction->uuid
                ];
                
            case 'transaction_approved':
            case 'transaction_rejected':
            case 'transaction_cancelled':
                // User notifications - minimal data
                return [
                    'amount' => $this->transaction->amount . ' DZD',
                    'coins_credited' => $this->transaction->coins_credited
                ];
                
            case 'review_approved':
            case 'review_rejected':
                // User notifications - minimal data
                return [
                    'coins_credited' => $this->transaction->coins_credited
                ];
                
            default:
                return [];
        }
    }

    /**
     * Get notification priority type based on event type
     */
    protected function getPriorityType(): string
    {
        return match($this->eventType) {
            'transaction_created' => 'info',
            'review_requested' => 'info',
            'transaction_approved' => 'success',
            'review_approved' => 'success',
            'transaction_rejected' => 'danger',
            'review_rejected' => 'danger',
            'transaction_cancelled' => 'warning',
            default => 'info'
        };
    }

    /**
     * Get the notification link based on event type and recipient
     */
    protected function getNotificationLink(): string
    {
        switch ($this->eventType) {
            case 'transaction_created':
            case 'review_requested':
                // Admin links
                return route('admin.payment.transactions');
                
            default:
                // User links
                return route('payment.transactions.show', $this->transaction->uuid);
        }
    }
} 