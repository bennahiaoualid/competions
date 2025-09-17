<?php

namespace App\Services\Notification;

use App\Models\User;
use App\Models\Admin\Admin;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Enums\NotificationClassTypes;
use App\Models\Payment\PaymentTransaction;
use App\Jobs\Notifications\BatchBroadcastJob;
use App\Jobs\Notifications\BatchNotificationJob;

class PaymentNotificationService
{
    /**
     * Notify accountants about new payment transaction
     */
    public function transactionCreated(PaymentTransaction $transaction): void
    {
        $accountants = $this->getAccountantsWithPermission();
        
        if ($accountants->isEmpty()) {
            Log::warning('No accountants found to notify about new payment', [
                'transaction_id' => $transaction->id
            ]);
            return;
        }

        $notificationData = $this->prepareAdminNotificationData($transaction, 'transaction_created');
        $this->notifyAccountants($accountants, $notificationData);
    }

    /**
     * Notify payment owner about approval
     */
    public function transactionApproved(PaymentTransaction $transaction): void
    {
        $this->notifyPayable($transaction, 'transaction_approved');
    }

    /**
     * Notify payment owner about rejection
     */
    public function transactionRejected(PaymentTransaction $transaction): void
    {
        Log::info('transactionRejected', ['transaction' => $transaction]);
        $this->notifyPayable($transaction, 'transaction_rejected');
    }

    /**
     * Notify payment owner about cancellation
     */
    public function transactionCancelled(PaymentTransaction $transaction): void
    {
        $this->notifyPayable($transaction, 'transaction_cancelled');
    }

    /**
     * Notify accountants about review request
     */
    public function reviewRequested(PaymentTransaction $transaction): void
    {
        $accountants = $this->getAccountantsWithPermission();
        
        if ($accountants->isEmpty()) {
            Log::warning('No accountants found to notify about review request', [
                'transaction_id' => $transaction->id
            ]);
            return;
        }

        $notificationData = $this->prepareAdminNotificationData($transaction, 'review_requested');
        $this->notifyAccountants($accountants, $notificationData);
    }

    /**
     * Notify payment owner about review approval
     */
    public function reviewApproved(PaymentTransaction $transaction): void
    {
        $this->notifyPayable($transaction, 'review_approved');
    }

    /**
     * Notify payment owner about review rejection
     */
    public function reviewRejected(PaymentTransaction $transaction): void
    {
        $this->notifyPayable($transaction, 'review_rejected');
    }

    /**
     * Get all admins with accountant role OR manage_payment permission
     */
    private function getAccountantsWithPermission(): Collection
    {
        return Admin::whereHas('roles', function($query) {
            $query->where('name', 'accountant');
        })->orWhereHas('permissions', function($query) {
            $query->where('name', 'manage_payment');
        })->get();
    }

    /**
     * Notify accountants with admin notification data
     */
    private function notifyAccountants(Collection $accountants, array $notificationData): void
    {
        $adminIds = $accountants->pluck('id')->toArray();
        $eventType = $notificationData['event_type'];
        
        // Database notifications (always sent)
        BatchNotificationJob::dispatch($adminIds, $notificationData, NotificationClassTypes::PAYMENT->value, Admin::class);
        
        // Broadcast notifications only for specific event types
        if ($this->shouldBroadcast($eventType)) {
            BatchBroadcastJob::dispatch($adminIds, $notificationData, NotificationClassTypes::PAYMENT, Admin::class);
        }
    }

    /**
     * Notify the payment owner (user or admin)
     */
    private function notifyPayable(PaymentTransaction $transaction, string $eventType): void
    {
        $payer = $transaction->payable;
        
        if (!$payer) {
            Log::error('Payment transaction has no payable entity', [
                'transaction_id' => $transaction->id
            ]);
            return;
        }

        $notificationData = $this->prepareUserNotificationData($transaction, $eventType);

        BatchNotificationJob::dispatch([$payer->id], $notificationData, NotificationClassTypes::PAYMENT->value, $transaction->payable_type);

        if ($this->shouldBroadcast($eventType)) {
            BatchBroadcastJob::dispatch([$payer->id], $notificationData, NotificationClassTypes::PAYMENT);
        }

    }

    /**
     * Prepare admin notification data (minimal)
     */
    private function prepareAdminNotificationData(PaymentTransaction $transaction, string $eventType): array
    {
        $payer = $transaction->payable;
        
        return [
            'translation_key' => "notifications.payment.{$eventType}",
            'translation_data' => [
                'payer_name' => $payer->name,
                'payer_type' => $payer instanceof User ? 'user' : 'admin',
                'amount' => $transaction->amount . ' DZD',
                'transaction_uuid' => $transaction->uuid
            ],
            'notification_priority_type' => $this->getPriorityType($eventType),
            'link' => $this->getNotificationLink($transaction, $eventType),
            'payment_transaction_id' => $transaction->id,
            'event_type' => $eventType,
            'type' => 'payment_event'
        ];
    }

    /**
     * Prepare user notification data (minimal)
     */
    private function prepareUserNotificationData(PaymentTransaction $transaction, string $eventType): array
    {
        $baseData = [
            'translation_key' => "notifications.payment.{$eventType}",
            'notification_priority_type' => $this->getPriorityType($eventType),
            'link' => $this->getNotificationLink($transaction, $eventType),
            'payment_transaction_id' => $transaction->id,
            'event_type' => $eventType,
            'type' => 'payment_event'
        ];

        // Add minimal translation data based on event type
        switch ($eventType) {
            case 'transaction_approved':
            case 'transaction_rejected':
            case 'transaction_cancelled':
                $baseData['translation_data'] = [
                    'amount' => $transaction->amount . ' DZD',
                    'coins_credited' => $transaction->coins_credited
                ];
                break;
                
            case 'review_approved':
            case 'review_rejected':
                $baseData['translation_data'] = [
                    'coins_credited' => $transaction->coins_credited
                ];
                break;
                
            default:
                $baseData['translation_data'] = [];
        }

        return $baseData;
    }

    /**
     * Get notification priority type based on event type
     */
    private function getPriorityType(string $eventType): string
    {
        return match($eventType) {
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
     * Determine if event should be broadcast in real-time
     */
    private function shouldBroadcast(string $eventType): bool
    {
        return match($eventType) {
            'transaction_created' => true,    // Broadcast new payment to accountants
            'review_requested' => true,       // Broadcast review request to accountants
            'transaction_rejected' => true,   // Broadcast rejection to payment owner
            'transaction_cancelled' => true,  // Broadcast cancellation to payment owner
            'transaction_approved' => false,  // No broadcast for approval (just database)
            'review_approved' => false,       // No broadcast for review approval (just database)
            'review_rejected' => false,      // No broadcast for review rejection (just database)
            default => false
        };
    }

    private function getNotificationLink(PaymentTransaction $transaction, string $eventType): string
    {
        switch($eventType) {
            case 'transaction_approved':
            case 'transaction_rejected':
            case 'transaction_cancelled':
            case 'review_approved':
            case 'review_rejected':
                return route('payment.transactions.show', $transaction->uuid);
            case 'transaction_created':
                return route('admin.payment.transactions');
            case 'review_requested':
                return route('admin.payment.reviews.index');
            default:
                return route('payment.transactions.show', $transaction->uuid);
        }
    }

} 