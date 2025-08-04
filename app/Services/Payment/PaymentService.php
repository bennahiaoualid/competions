<?php

namespace App\Services\Payment;

use Exception;
use App\Traits\RegisterLogs;
use App\Contracts\FlasherInterface;
use App\Models\Payment\CoinBalance;
use Illuminate\Support\Facades\Auth;
use App\Models\Payment\PaymentAuditLog;
use Illuminate\Support\Facades\Storage;
use App\Models\Payment\PaymentTransaction;
use App\Contracts\TransactionManagerInterface;

class PaymentService
{
    use RegisterLogs;

    public function __construct(
        protected TransactionManagerInterface $transactionManager,
        protected FlasherInterface $flasher
    ) {}

    /**
     * Create a new payment transaction
     */
    public function createPayment(array $data): PaymentTransaction
    {
        return $this->transactionManager->run(function () use ($data) {
            $payment = PaymentTransaction::create($data);
            
            // Log the creation
            $this->logPaymentAction($payment, 'created', null, $data);
            
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
     * Delete payment proof image
     */
    public function deleteProofImage(PaymentTransaction $payment): bool
    {
        if ($payment->proof_image_path) {
            return Storage::disk('payment_proofs')->delete($payment->proof_image_path);
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
                // Calculate coins based on amount (this will be dynamic later)
                $coins = $this->calculateCoins($request->amount);
                
                // Store proof image
                $proofPath = $request->file('proof_image')->store('payment_proofs', 'payment_proofs');
                
                // Create payment transaction
                $payment = $this->createPayment([
                    'payable_id' => Auth::id(),
                    'payable_type' => get_class(Auth::user()),
                    'amount' => $request->amount,
                    'coins_credited' => $coins,
                    'payment_method' => $request->payment_method,
                    'proof_image_path' => $proofPath,
                    'status' => 'pending'
                ]);

                return true;
            });

            $this->flasher->crudSuccess('saved');
            return $result;

        } catch (\Exception $e) {
            $this->registerLogs('Payment creation error: ', $e);
            $this->flasher->crudFailure('saved');
            return false;
        }
    }

    /**
     * Calculate coins based on amount (temporary - will be replaced with dynamic pricing)
     */
    private function calculateCoins(float $amount): int
    {
        // Temporary calculation: 100 DZD = 50 coins for users
        return (int) ($amount / 100 * 50);
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