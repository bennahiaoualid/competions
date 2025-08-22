<?php

namespace App\Services\Payment;

use Exception;
use App\Traits\RegisterLogs;
use App\Contracts\FlasherInterface;
use Illuminate\Support\Facades\Auth;
use App\Models\Payment\PaymentTransaction;
use App\Models\Payment\PaymentReviewRequest;
use App\Contracts\TransactionManagerInterface;
use App\Services\Notification\PaymentNotificationService;

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

                if($transaction->approved_at < now()->subHours(48)) {
                    $this->flasher->error(__('payment.review.messages.review_period_passed'));
                    return false;
                }

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
                $isPaymentApproved = $this->paymentService->approvePayment($transaction, $observation);
                if(!$isPaymentApproved){
                    throw new Exception('Payment with id '.$transaction->id.' not approved');
                }

                // Send notification to payment owner about review approval
                $this->notificationService->reviewApproved($transaction);

                $this->flasher->crudSuccess('updated');
                return true;
            });
        } catch (Exception $e) {
            $this->registerLogs('PaymentReviewService:approve',$e);
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
        } catch (Exception $e) {
            $this->registerLogs('PaymentReviewService:reject',$e);
            $this->flasher->crudFailure('updated');
            return false;
        }
    }
} 