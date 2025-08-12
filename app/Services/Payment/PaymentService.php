<?php

namespace App\Services\Payment;

use Exception;
use App\Enums\UserTypeEnum;
use App\Traits\RegisterLogs;
use App\Traits\ImageManipulation;
use App\Contracts\FlasherInterface;
use App\Models\Payment\CoinBalance;
use App\Models\Payment\CoinPricing;
use Illuminate\Support\Facades\Auth;
use App\Models\Payment\PaymentAuditLog;
use Illuminate\Support\Facades\Storage;
use App\Models\Payment\PaymentTransaction;
use App\Services\Payment\CoinPricingService;
use App\Services\Notification\PaymentNotificationService;
use App\Contracts\TransactionManagerInterface;
use App\Helpers\PaginationHelper;
use App\Events\Payment\PaymentCacheInvalidationEvent;

class PaymentService
{
    use RegisterLogs, ImageManipulation;

    public function __construct(
        protected TransactionManagerInterface $transactionManager,
        protected FlasherInterface $flasher,
        protected CoinPricingService $coinPricingService,
        protected PaymentNotificationService $notificationService
    ) {}

    public function getTransactionsForUser(array $filters = [], $page = 1, int $perPage = 5)
    {
        $user = Auth::user();
        
        $query = PaymentTransaction::query()
            ->where('payable_id', $user->id)
            ->where('payable_type', get_class($user));
        
        // Apply search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('uuid', 'like', "%{$search}%")
                  ->orWhere('amount', 'like', "%{$search}%")
                  ->orWhere('coins_credited', 'like', "%{$search}%");
            });
        }
        
        // Apply status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        // Order and paginate
        return $query->orderBy('created_at', 'desc')
                ->paginate($perPage, page: $page);
    }

    /**
     * Get status counts for user transactions
     */
    public function getUserTransactionStatusCounts(): array
    {
        $user = Auth::user();
        
        $statusCounts = PaymentTransaction::where('payable_id', $user->id)
            ->where('payable_type', get_class($user))
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        
        // Ensure all statuses are represented
        $allStatuses = ['pending', 'approved', 'rejected', 'cancelled'];
        foreach ($allStatuses as $status) {
            if (!isset($statusCounts[$status])) {
                $statusCounts[$status] = 0;
            }
        }
        
        return $statusCounts;
    }

    /**
     * Create a new payment transaction
     */
    public function createPayment(array $data): PaymentTransaction
    {
        return $this->transactionManager->run(function () use ($data) {
            $payment = PaymentTransaction::create($data);
            
            // Log the creation
            $this->logPaymentAction($payment, 'created', null, $data);
            
            // Fire cache invalidation event
            $this->fireCacheInvalidationEvent($payment);
            
            // Send notification to accountants
            $this->notificationService->transactionCreated($payment);
            
            return $payment;
        });
    }

    /**
     * Approve a payment transaction
     */
    public function approvePayment(PaymentTransaction $payment, ?string $observation = null): bool
    {
        try {
            return $this->transactionManager->run(function () use ($payment, $observation) {
                $adminId = Auth::id();
                $oldValues = $payment->toArray();
                
                // Update payment status
                $payment->update([
                    'status' => 'approved',
                    'approver_admin_id' => $adminId,
                    'approved_at' => now(),
                    'accountant_observation' => $observation
                ]);
                
                // Credit coins to user
                $this->creditCoinsToUser($payment);
                
                // Log the approval
                $this->logPaymentAction($payment, 'approved', $oldValues, $payment->toArray(), $adminId);
                
                // Fire cache invalidation event
                $this->fireCacheInvalidationEvent($payment);
                
                // Send notification to payment owner
                $this->notificationService->transactionApproved($payment);
                
                $this->flasher->crudSuccess('payment.approved');
                return true;
            });
        } catch (Exception $e) {
            $this->registerLogs('Payment Service: Payment approval error: ', $e);
            $this->flasher->crudFailure('payment.approved');
            return false;
        }
        
    }

    /**
     * Reject a payment transaction
     */
    public function rejectPayment(PaymentTransaction $payment, ?string $observation = null): bool
    {
        try {
            return $this->transactionManager->run(function () use ($payment, $observation) {
                $adminId = Auth::id();
                $oldValues = $payment->toArray();
                
                // Update payment status
                $payment->update([
                    'status' => 'rejected',
                    'approver_admin_id' => $adminId,
                    'approved_at' => now(),
                    'accountant_observation' => $observation
                ]);
                
                // Log the rejection
                $this->logPaymentAction($payment, 'rejected', $oldValues, $payment->toArray(), $adminId);
                
                // Fire cache invalidation event
                $this->fireCacheInvalidationEvent($payment);
                
                // Send notification to payment owner
                $this->notificationService->transactionRejected($payment);
                
                $this->flasher->crudSuccess('payment.rejected');
                return true;
            });
        } catch (Exception $e) {
            $this->registerLogs('Payment Service: Payment rejection error: ', $e);
            $this->flasher->crudFailure('payment.rejected');
            return false;
        }
    }

    /**
     * Cancel a payment transaction
     */
    public function cancelPayment(PaymentTransaction $payment, ?string $observation = null): bool
    {
        try {
            return $this->transactionManager->run(function () use ($payment, $observation) {
                $adminId = Auth::id();
                $oldValues = $payment->toArray();
                
                // Update payment status
                $payment->update([
                    'status' => 'cancelled',
                    'approver_admin_id' => $adminId,
                    'approved_at' => now(),
                    'accountant_observation' => $observation
                ]);
                
                // Log the cancellation
                $this->logPaymentAction($payment, 'cancelled', $oldValues, $payment->toArray(), $adminId);
                
                // Fire cache invalidation event
                $this->fireCacheInvalidationEvent($payment);
                
                // Send notification to payment owner
                $this->notificationService->transactionCancelled($payment);
                
                $this->flasher->crudSuccess('payment.cancelled');
                return true;
            });
        } catch (Exception $e) {
            $this->registerLogs('Payment Service: Payment cancellation error: ', $e);
            $this->flasher->crudFailure('payment.cancelled');
            return false;
        }
    }

    /**
     * Credit coins to user/admin
     */
    private function creditCoinsToUser(PaymentTransaction $payment): void
    {
        $payable = $payment->payable;
        
        // Get or create coin balance
        $coinBalance = $payable->coinBalance()->firstOrCreate([
            'balanceable_id' => $payable->id,
            'balanceable_type' => get_class($payable)
        ]);
        
        // Add coins
        $coinBalance->addCoins($payment->coins_credited);
    }

    /**
     * Log payment action for audit trail
     */
    private function logPaymentAction(
        PaymentTransaction $payment, 
        string $action, 
        ?array $oldValues, 
        ?array $newValues, 
        ?int $adminId = null
    ): void {
        PaymentAuditLog::create([
            'payment_transaction_id' => $payment->id,
            'admin_id' => $adminId ?? (Auth::check() ? Auth::id() : null),
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }

    /**
     * Fire cache invalidation event for a payment
     */
    private function fireCacheInvalidationEvent(PaymentTransaction $payment, string $eventType = 'invalidateAllUserPaymentCaches'): void
    {
        PaymentCacheInvalidationEvent::dispatch(
            $eventType,
            ['userId' => $payment->payable_id]
        );
    }

    /**
     * Delete payment proof image
     */
    public function deleteProofImage(PaymentTransaction $payment): bool
    {
        if ($payment->proof_image_path) {
            // Get thumbnails from payment metadata if available
            $thumbnails = $payment->metadata['thumbnails'] ?? [];
            
            /*return $this->deleteImage(
                $payment->proof_image_path,
                'payment_proofs',
                $thumbnails
            );*/
        }
        
        return true;
    }

    /**
     * Create payment from request (with business logic and error handling)
     */
    public function createPaymentFromRequest($request): bool
    {
        try {
            
            $result = $this->transactionManager->run(function () use ($request) {
                // Condition 1: Validate the image
                $imageValidationErrors = $this->validateImage($request->file('proof_image'), [
                    'max_size' => 10240, // 10MB
                    'allowed_mimes' => ['jpeg', 'jpg', 'png', 'gif'],
                    'min_width' => 100,
                    'max_width' => 5000,
                    'min_height' => 100,
                    'max_height' => 5000,
                ]);

                if (!empty($imageValidationErrors)) {
                    $translatedErrors = $this->translateImageErrors($imageValidationErrors);
                    foreach ($translatedErrors as $error) {
                        $this->flasher->error($error);
                    }
                    return false;
                }

                // Get the selected coin pricing
                $coinPricing = CoinPricing::with('activeOffer')->findOrFail($request->coin_pricing_id);
                
                // Determine user type
                $userType = Auth::user()->getUserType()->value;
                // Condition 2: Validate coin pricing matches user type
                if ($coinPricing->user_type !== $userType && $coinPricing->user_type !== 'both') {
                    $this->flasher->error(__('messages.validation.not_allow.transcation_incorrect_user_type'));
                    return false;
                }
                
                // Calculate coins using the selected pricing
                $coins = $this->coinPricingService->calculateCoins($coinPricing);
                // Process proof image with optimization
                $imageResult = $this->saveImage(
                    $request->file('proof_image'),
                    config('image.private_types.transaction.path'),
                    [
                        'disk' => config('image.private_types.transaction.disk'),
                        'quality' => config('image.private_types.transaction.quality'),
                        'max_width' => config('image.private_types.transaction.max_width'),
                        'max_height' => config('image.private_types.transaction.max_height'),
                        'format' => config('image.format'),
                    ]
                );

                if (!$imageResult['success']) {
                    $this->flasher->error(__('messages.validation.images.processing_failed'));
                    return false;
                }

                $proofPath = $imageResult['path'];
                
                // Create payment transaction
                $payment = $this->createPayment([
                    'payable_id' => Auth::id(),
                    'payable_type' => get_class(Auth::user()),
                    'amount' => $coinPricing->base_amount,
                    'coins_credited' => $coins,
                    'payment_method' => $request->payment_method,
                    'proof_image_path' => $proofPath,
                    'status' => 'pending',
                ]);

                return true;
            });

            if ($result) {
                $this->flasher->crudSuccess('saved');
            }
            
            return $result;

        } catch (\Exception $e) {
            $this->registerLogs('Payment creation error: ', $e);
            $this->flasher->crudFailure('saved');
            return false;
        }
    }

    public function getCoinPricingForUser()
    {
        $userType = Auth::user()->getUserType();
        $coinPricing = CoinPricing::with('activeOffer')
                        ->active()
                        ->forUserType($userType->value)
                        ->get();
        return $coinPricing;
    }

    /**
     * Get payment statistics
     */
    public function getPaymentStats(): array
    {
        return [
            'total_payments' => PaymentTransaction::count(),
            'pending_payments' => PaymentTransaction::pending()->count(),
            'approved_payments' => PaymentTransaction::approved()->count(),
            'rejected_payments' => PaymentTransaction::rejected()->count(),
            'cancelled_payments' => PaymentTransaction::cancelled()->count(),
            'total_amount' => PaymentTransaction::approved()->sum('amount'),
            'total_coins_credited' => PaymentTransaction::approved()->sum('coins_credited'),
        ];
    }
} 