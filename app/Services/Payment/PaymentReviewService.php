<?php

namespace App\Services\Payment;

use App\Contracts\FlasherInterface;
use App\Contracts\TransactionManagerInterface;
use App\Models\Payment\PaymentReviewRequest;
use App\Models\Payment\PaymentTransaction;
use App\Services\Notification\PaymentNotificationService;
use App\Traits\RegisterLogs;
use Illuminate\Support\Facades\Auth;

class PaymentReviewService
{
    use RegisterLogs;
    public function __construct(
        private TransactionManagerInterface $transactionManager,
        private FlasherInterface $flasher,
        private PaymentService $paymentService,
        private PaymentNotificationService $notificationService
    ) {}

    public function requestReview(PaymentTransaction $transaction, string $reason): bool
    {
        try {
            return $this->transactionManager->run(function () use ($transaction, $reason) {
                // Ensure no pending review exists for this transaction
                $existing = PaymentReviewRequest::where('payment_transaction_id', $transaction->id)
                    ->first();
                if ($existing) {
                    $this->flasher->error(__('payment.review.messages.already_exists'));
                    return false;
                }

                PaymentReviewRequest::create([
                    'payment_transaction_id' => $transaction->id,
                    'request_reason' => $reason,
                    'status' => 'pending',
                ]);

                // Send notification to accountants
                $this->notificationService->reviewRequested($transaction);

                $this->flasher->crudSuccess('saved');
                return true;
            });
        } catch (\Throwable $e) {
            $this->registerLogs('PaymentReviewService',$e);
            $this->flasher->crudFailure('saved');
            return false;
        }
    }

    public function approve(int $reviewId, ?string $observation = null): bool
    {
        try {
            return $this->transactionManager->run(function () use ($reviewId, $observation) {
                $review = PaymentReviewRequest::findOrFail($reviewId);
                if ($review->status !== 'pending') {
                    $this->flasher->error(__('payment.review.not_pending'));
                    return false;
                }

                $review->update([
                    'status' => 'approved',
                    'reviewed_by_admin_id' => Auth::id(),
                    'reviewed_at' => now(),
                    'review_observation' => $observation,
                ]);

                $transaction = $review->paymentTransaction;
                // Delegate to PaymentService to handle coin credit + status change
                $this->paymentService->approvePayment($transaction, $observation);

                // Send notification to payment owner about review approval
                $this->notificationService->reviewApproved($transaction);

                $this->flasher->crudSuccess('updated');
                return true;
            });
        } catch (\Throwable $e) {
            $this->flasher->crudFailure('updated');
            return false;
        }
    }

    public function reject(int $reviewId, string $observation): bool
    {
        try {
            return $this->transactionManager->run(function () use ($reviewId, $observation) {
                $review = PaymentReviewRequest::findOrFail($reviewId);
                if ($review->status !== 'pending') {
                    $this->flasher->error(__('payment.review.not_pending'));
                    return false;
                }

                $review->update([
                    'status' => 'rejected',
                    'reviewed_by_admin_id' => Auth::id(),
                    'reviewed_at' => now(),
                    'review_observation' => $observation,
                ]);

                // Get transaction for notification
                $transaction = $review->paymentTransaction;

                // Send notification to payment owner about review rejection
                $this->notificationService->reviewRejected($transaction);

                $this->flasher->crudSuccess('updated');
                return true;
            });
        } catch (\Throwable $e) {
            $this->flasher->crudFailure('updated');
            return false;
        }
    }
} 