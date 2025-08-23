<?php

namespace Tests\Unit\Services\Payment;

use Mockery;
use Tests\TestCase;
use App\Models\User;
use App\Models\Admin\Admin;
use App\Models\Payment\PaymentTransaction;
use App\Models\Payment\PaymentReviewRequest;
use App\Services\Payment\PaymentReviewService;
use App\Services\Payment\PaymentService;
use App\Services\Notification\PaymentNotificationService;
use App\Contracts\FlasherInterface;
use App\Contracts\TransactionManagerInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

class PaymentReviewServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $paymentReviewService;
    protected $transactionManager;
    protected $flasher;
    protected $paymentService;
    protected $notificationService;
    protected $user;
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->transactionManager = Mockery::mock(TransactionManagerInterface::class);
        $this->flasher = Mockery::mock(FlasherInterface::class);
        $this->paymentService = Mockery::mock(PaymentService::class);
        $this->notificationService = Mockery::mock(PaymentNotificationService::class);
        
        $this->paymentReviewService = new PaymentReviewService(
            $this->transactionManager,
            $this->flasher,
            $this->paymentService,
            $this->notificationService
        );

        // Create test user and admin
        $this->user = User::factory()->create();
        $this->admin = Admin::factory()->create();
        
        // Mock Auth facade
        Auth::shouldReceive('id')->andReturn($this->admin->id);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_request_review_success()
    {
        $transaction = PaymentTransaction::factory()->create([
            'status' => 'approved',
            'approved_at' => now()->subHours(24), // Within 48-hour window
        ]);

        $reason = 'Payment amount discrepancy';

        $this->transactionManager->shouldReceive('run')
            ->once()
            ->with(Mockery::type('Closure'))
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $this->notificationService->shouldReceive('reviewRequested')
            ->once()
            ->with($transaction);

        $this->flasher->shouldReceive('crudSuccess')
            ->once()
            ->with('saved');

        $result = $this->paymentReviewService->requestReview($transaction, $reason);

        $this->assertTrue($result);
        $this->assertDatabaseHas('payment_review_requests', [
            'payment_transaction_id' => $transaction->id,
            'request_reason' => $reason,
            'status' => 'pending',
        ]);
    }

    public function test_request_review_fails_when_period_expired()
    {
        $transaction = PaymentTransaction::factory()->create([
            'status' => 'approved',
            'approved_at' => now()->subHours(49), // Outside 48-hour window
        ]);

        $reason = 'Payment amount discrepancy';

        $this->transactionManager->shouldReceive('run')
        ->once()
        ->with(Mockery::type('Closure'))
        ->andReturnUsing(function ($callback) {
            return $callback();
        });

        $this->flasher->shouldReceive('error')
            ->once()
            ->with(__('payment.review.messages.review_period_passed'));

        $result = $this->paymentReviewService->requestReview($transaction, $reason);

        $this->assertFalse($result);
        $this->assertDatabaseMissing('payment_review_requests', [
            'payment_transaction_id' => $transaction->id,
        ]);
    }

    public function test_request_review_fails_when_review_already_exists()
    {
        $transaction = PaymentTransaction::factory()->create([
            'status' => 'approved',
            'approved_at' => now()->subHours(24),
        ]);

        // Create existing review
        PaymentReviewRequest::factory()->create([
            'payment_transaction_id' => $transaction->id,
            'status' => 'pending',
        ]);

        $reason = 'Payment amount discrepancy';

        $this->transactionManager->shouldReceive('run')
        ->once()
        ->with(Mockery::type('Closure'))
        ->andReturnUsing(function ($callback) {
            return $callback();
        });

        $this->flasher->shouldReceive('error')
            ->once()
            ->with(__('payment.review.messages.already_exists'));

        $result = $this->paymentReviewService->requestReview($transaction, $reason);

        $this->assertFalse($result);
        $this->assertDatabaseCount('payment_review_requests', 1);
    }

    public function test_request_review_handles_exception()
    {
        $transaction = PaymentTransaction::factory()->create([
            'status' => 'approved',
            'approved_at' => now()->subHours(24),
        ]);

        $reason = 'Payment amount discrepancy';

        $this->transactionManager->shouldReceive('run')
            ->once()
            ->andThrow(new \Exception('Database error'));

        $this->flasher->shouldReceive('crudFailure')
            ->once()
            ->with('saved');

        $result = $this->paymentReviewService->requestReview($transaction, $reason);

        $this->assertFalse($result);
    }

    public function test_approve_review_success()
    {
        $transaction = PaymentTransaction::factory()->create([
            'status' => 'approved',
            'approved_at' => now()->subHours(24),
        ]);

        $review = PaymentReviewRequest::factory()->create([
            'payment_transaction_id' => $transaction->id,
            'status' => 'pending',
        ]);


        $observation = 'Review approved after investigation';

        $this->transactionManager->shouldReceive('run')
            ->once()
            ->with(Mockery::type('Closure'))
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $this->paymentService->shouldReceive('approvePayment')
            ->once()
            ->with(Mockery::type(PaymentTransaction::class), $observation)
            ->andReturn(true);

        $this->notificationService->shouldReceive('reviewApproved')
            ->once()
            ->with(Mockery::type(PaymentTransaction::class));

        $this->flasher->shouldReceive('crudSuccess')
            ->once()
            ->with('updated');

        $result = $this->paymentReviewService->approve($review->id, $observation);

        $this->assertTrue($result);
        $this->assertDatabaseHas('payment_review_requests', [
            'id' => $review->id,
            'status' => 'approved',
            'reviewed_by_admin_id' => $this->admin->id,
            'review_observation' => $observation,
        ]);
    }

    public function test_approve_review_fails_when_not_pending()
    {
        $transaction = PaymentTransaction::factory()->create([
            'status' => 'approved',
        ]);

        $review = PaymentReviewRequest::factory()->create([
            'payment_transaction_id' => $transaction->id,
            'status' => 'approved', // Already approved
        ]);

        $observation = 'Review approved after investigation';

        $this->transactionManager->shouldReceive('run')
        ->once()
        ->with(Mockery::type('Closure'))
        ->andReturnUsing(function ($callback) {
            return $callback();
        });

        $this->flasher->shouldReceive('error')
            ->once()
            ->with(__('payment.review.not_pending'));

        $result = $this->paymentReviewService->approve($review->id, $observation);

        $this->assertFalse($result);
    }

    public function test_approve_review_handles_exception()
    {
        $transaction = PaymentTransaction::factory()->create([
            'status' => 'approved',
        ]);

        $review = PaymentReviewRequest::factory()->create([
            'payment_transaction_id' => $transaction->id,
            'status' => 'pending',
        ]);

        $observation = 'Review approved after investigation';

        $this->transactionManager->shouldReceive('run')
        ->once()
        ->with(Mockery::type('Closure'))
        ->andReturnUsing(function ($callback) {
            return $callback();
        });

        $this->paymentService->shouldReceive('approvePayment')
            ->once()
            ->with(Mockery::type(PaymentTransaction::class), $observation)
            ->andReturn(false);

        $this->flasher->shouldReceive('crudFailure')
            ->once()
            ->with('updated');

        $result = $this->paymentReviewService->approve($review->id, $observation);

        $this->assertFalse($result);
    }

    public function test_reject_review_success()
    {
        $transaction = PaymentTransaction::factory()->create([
            'status' => 'approved',
        ]);

        $review = PaymentReviewRequest::factory()->create([
            'payment_transaction_id' => $transaction->id,
            'status' => 'pending',
        ]);

        $observation = 'Review rejected due to insufficient evidence';

        $this->transactionManager->shouldReceive('run')
            ->once()
            ->with(Mockery::type('Closure'))
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $this->notificationService->shouldReceive('reviewRejected')
            ->once()
            ->with(Mockery::type(PaymentTransaction::class));

        $this->flasher->shouldReceive('crudSuccess')
            ->once()
            ->with('updated');

        $result = $this->paymentReviewService->reject($review->id, $observation);

        $this->assertTrue($result);
        $this->assertDatabaseHas('payment_review_requests', [
            'id' => $review->id,
            'status' => 'rejected',
            'reviewed_by_admin_id' => $this->admin->id,
            'review_observation' => $observation,
        ]);
    }

    public function test_reject_review_fails_when_not_pending()
    {
        $transaction = PaymentTransaction::factory()->create([
            'status' => 'approved',
        ]);

        $review = PaymentReviewRequest::factory()->create([
            'payment_transaction_id' => $transaction->id,
            'status' => 'rejected', // Already rejected
        ]);

        $observation = 'Review rejected due to insufficient evidence';

        $this->transactionManager->shouldReceive('run')
        ->once()
        ->with(Mockery::type('Closure'))
        ->andReturnUsing(function ($callback) {
            return $callback();
        });

        $this->flasher->shouldReceive('error')
            ->once()
            ->with(__('payment.review.not_pending'));

        $result = $this->paymentReviewService->reject($review->id, $observation);

        $this->assertFalse($result);
    }

    public function test_reject_review_handles_exception()
    {
        $transaction = PaymentTransaction::factory()->create([
            'status' => 'approved',
        ]);

        $review = PaymentReviewRequest::factory()->create([
            'payment_transaction_id' => $transaction->id,
            'status' => 'pending',
        ]);

        $observation = 'Review rejected due to insufficient evidence';

        $this->transactionManager->shouldReceive('run')
            ->once()
            ->andThrow(new \Exception('Database error'));

        $this->flasher->shouldReceive('crudFailure')
            ->once()
            ->with('updated');

        $result = $this->paymentReviewService->reject($review->id, $observation);

        $this->assertFalse($result);
    }

} 