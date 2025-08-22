<?php

namespace Tests\Unit\Services\Payment;

use Mockery;
use Exception;
use Tests\TestCase;
use App\Models\User;
use App\Models\Admin\Admin;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use App\Contracts\FlasherInterface;
use App\Models\Payment\CoinBalance;
use App\Models\Payment\CoinPricing;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use App\Services\Payment\PaymentService;
use App\Models\Payment\PaymentTransaction;
use App\Services\Payment\CoinPricingService;
use App\Contracts\TransactionManagerInterface;
use App\Services\Payment\CoinTransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Events\Payment\PaymentCacheInvalidationEvent;
use App\Services\Notification\PaymentNotificationService;

use function Laravel\Prompts\error;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $paymentService;
    protected $transactionManager;
    protected $flasher;
    protected $coinPricingService;
    protected $notificationService;
    protected $coinTransactionService;
    protected $user;
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->transactionManager = Mockery::mock(TransactionManagerInterface::class);
        $this->flasher = Mockery::mock(FlasherInterface::class);
        $this->coinPricingService = Mockery::mock(CoinPricingService::class);
        $this->notificationService = Mockery::mock(PaymentNotificationService::class);
        $this->coinTransactionService = Mockery::mock(CoinTransactionService::class);
        
        // Create a partial mock of PaymentService to mock trait methods
        $this->paymentService = Mockery::mock(PaymentService::class, [
            $this->transactionManager,
            $this->flasher,
            $this->coinPricingService,
            $this->notificationService,
            $this->coinTransactionService,
        ])->makePartial();

        // Create test user and admin
        $this->user = User::factory()->create();
        $this->admin = Admin::factory()->create();
        
        // Fake events to avoid actual event dispatching during tests
        Event::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_user_transaction_count_for_day()
    {
        // Act as the authenticated user
        $this->actingAs($this->user);
        // Create transactions for today
        PaymentTransaction::factory()->count(3)->create([
            'payable_id' => $this->user->id,
            'payable_type' => User::class,
            'created_at' => now()
        ]);

        // Create transaction for yesterday (should not count)
        PaymentTransaction::factory()->create([
            'payable_id' => $this->user->id,
            'payable_type' => User::class,
            'created_at' => now()->subDay()
        ]);

        $result = $this->paymentService->getUserTransactionCountForDay();

        $this->assertEquals(3, $result);
    }

    public function test_get_transactions_for_user_without_filters()
    {
        // Act as the authenticated user
        $this->actingAs($this->user);
        // Create some transactions for the user
        PaymentTransaction::factory()->count(5)->create([
            'payable_id' => $this->user->id,
            'payable_type' => User::class
        ]);

        $result = $this->paymentService->getTransactionsForUser();

        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $result);
        $this->assertEquals(5, $result->total());
        $this->assertEquals(5, $result->perPage());
    }

    public function test_get_transactions_for_user_with_search_filter()
    {
        // Act as the authenticated user
        $this->actingAs($this->user);
        // Create transaction with specific UUID
        $transaction = PaymentTransaction::factory()->create([
            'payable_id' => $this->user->id,
            'payable_type' => User::class,
            'uuid' => 'test-uuid-123'
        ]);

        $result = $this->paymentService->getTransactionsForUser(['search' => 'test-uuid']);

        $this->assertEquals(1, $result->total());
        $this->assertEquals($transaction->id, $result->first()->id);
    }

    public function test_get_transactions_for_user_with_status_filter()
    {
        // Act as the authenticated user
        $this->actingAs($this->user);
        // Create transactions with different statuses
        PaymentTransaction::factory()->create([
            'payable_id' => $this->user->id,
            'payable_type' => User::class,
            'status' => 'pending'
        ]);

        PaymentTransaction::factory()->create([
            'payable_id' => $this->user->id,
            'payable_type' => User::class,
            'status' => 'approved'
        ]);

        $result = $this->paymentService->getTransactionsForUser(['status' => 'pending']);

        $this->assertEquals(1, $result->total());
        $this->assertEquals('pending', $result->first()->status);
    }

    public function test_get_user_transaction_status_counts()
    {
        // Act as the authenticated user
        $this->actingAs($this->user);
        // Create transactions with different statuses
        PaymentTransaction::factory()->count(2)->create([
            'payable_id' => $this->user->id,
            'payable_type' => User::class,
            'status' => 'pending'
        ]);

        PaymentTransaction::factory()->count(3)->create([
            'payable_id' => $this->user->id,
            'payable_type' => User::class,
            'status' => 'approved'
        ]);

        PaymentTransaction::factory()->create([
            'payable_id' => $this->user->id,
            'payable_type' => User::class,
            'status' => 'rejected'
        ]);

        $result = $this->paymentService->getUserTransactionStatusCounts();

        $this->assertEquals(2, $result['pending']);
        $this->assertEquals(3, $result['approved']);
        $this->assertEquals(1, $result['rejected']);
        $this->assertEquals(0, $result['cancelled']);
    }

    public function test_create_payment_success()
    {
        $data = [
            'uuid' => \Illuminate\Support\Str::uuid(),
            'payable_id' => $this->user->id,
            'payable_type' => User::class,
            'amount' => 100.00,
            'coins_credited' => 50,
            'payment_method' => 'bank_transfer',
            'proof_image_path' => 'proofs/test.jpg',
            'status' => 'pending'
        ];

        $this->transactionManager->shouldReceive('run')
            ->once()
            ->with(Mockery::type('Closure'))
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $this->notificationService->shouldReceive('transactionCreated')
            ->once()
            ->with(Mockery::type(PaymentTransaction::class));

        $result = $this->paymentService->createPayment($data);

        $this->assertInstanceOf(PaymentTransaction::class, $result);
        $this->assertEquals($this->user->id, $result->payable_id);
        $this->assertEquals(User::class, $result->payable_type);
        $this->assertEquals(100.00, $result->amount);
        $this->assertEquals(50, $result->coins_credited);
        $this->assertEquals('pending', $result->status);

        // Verify event was dispatched
        Event::assertDispatched(PaymentCacheInvalidationEvent::class);
    }

    public function test_approve_payment_success()
    {
        // Act as the authenticated admin
        $this->actingAs($this->admin);
        $payment = PaymentTransaction::factory()->create([
            'payable_id' => $this->user->id,
            'payable_type' => User::class,
            'status' => 'pending',
            'coins_credited' => 100
        ]);

        $observation = 'Payment approved after verification';

        $this->transactionManager->shouldReceive('run')
            ->once()
            ->with(Mockery::type('Closure'))
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $this->coinTransactionService->shouldReceive('createPurchasedTransaction')
            ->once()
            ->with(Mockery::type(User::class), 100);

        $this->notificationService->shouldReceive('transactionApproved')
            ->once()
            ->with($payment);

        $this->flasher->shouldReceive('crudSuccess')
            ->once()
            ->with('payment.approved');

        $result = $this->paymentService->approvePayment($payment, $observation);

        $this->assertTrue($result);
        
        // Verify payment was updated
        $payment->refresh();
        $this->assertEquals('approved', $payment->status);
        $this->assertEquals($this->admin->id, $payment->approver_admin_id);
        $this->assertEquals($observation, $payment->accountant_observation);
        $this->assertNotNull($payment->approved_at);

        // Verify event was dispatched
        Event::assertDispatched(PaymentCacheInvalidationEvent::class);
    }

    public function test_approve_payment_handles_exception()
    {
        // Act as the authenticated admin
        $this->actingAs($this->admin);
        $payment = PaymentTransaction::factory()->create([
            'payable_id' => $this->user->id,
            'payable_type' => User::class,
            'status' => 'pending'
        ]);

        $this->transactionManager->shouldReceive('run')
            ->once()
            ->andThrow(new Exception('Database error'));

        $this->flasher->shouldReceive('crudFailure')
            ->once()
            ->with('payment.approved');

        $result = $this->paymentService->approvePayment($payment);

        $this->assertFalse($result);
    }

    public function test_reject_payment_success()
    {
        // Act as the authenticated admin
        $this->actingAs($this->admin);
        $payment = PaymentTransaction::factory()->create([
            'payable_id' => $this->user->id,
            'payable_type' => User::class,
            'status' => 'pending'
        ]);

        $observation = 'Payment rejected due to insufficient proof';

        $this->transactionManager->shouldReceive('run')
            ->once()
            ->with(Mockery::type('Closure'))
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $this->notificationService->shouldReceive('transactionRejected')
            ->once()
            ->with($payment);

        $this->flasher->shouldReceive('crudSuccess')
            ->once()
            ->with('payment.rejected');

        $result = $this->paymentService->rejectPayment($payment, $observation);

        $this->assertTrue($result);
        
        // Verify payment was updated
        $payment->refresh();
        $this->assertEquals('rejected', $payment->status);
        $this->assertEquals($this->admin->id, $payment->approver_admin_id);
        $this->assertEquals($observation, $payment->accountant_observation);
        $this->assertNotNull($payment->approved_at);

        // Verify event was dispatched
        Event::assertDispatched(PaymentCacheInvalidationEvent::class);
    }

    public function test_reject_payment_handles_exception()
    {
        // Act as the authenticated admin
        $this->actingAs($this->admin);
        $payment = PaymentTransaction::factory()->create([
            'payable_id' => $this->user->id,
            'payable_type' => User::class,
            'status' => 'pending'
        ]);

        $this->transactionManager->shouldReceive('run')
            ->once()
            ->andThrow(new Exception('Database error'));

        $this->flasher->shouldReceive('crudFailure')
            ->once()
            ->with('payment.rejected');

        $result = $this->paymentService->rejectPayment($payment);

        $this->assertFalse($result);
    }

    public function test_cancel_payment_success()
    {
        // Act as the authenticated admin
        $this->actingAs($this->admin);
        $payment = PaymentTransaction::factory()->create([
            'payable_id' => $this->user->id,
            'payable_type' => User::class,
            'status' => 'pending'
        ]);

        $observation = 'Payment cancelled by user request';

        $this->transactionManager->shouldReceive('run')
            ->once()
            ->with(Mockery::type('Closure'))
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $this->notificationService->shouldReceive('transactionCancelled')
            ->once()
            ->with($payment);

        $this->flasher->shouldReceive('crudSuccess')
            ->once()
            ->with('payment.cancelled');

        $result = $this->paymentService->cancelPayment($payment, $observation);

        $this->assertTrue($result);
        
        // Verify payment was updated
        $payment->refresh();
        $this->assertEquals('cancelled', $payment->status);
        $this->assertEquals($this->admin->id, $payment->approver_admin_id);
        $this->assertEquals($observation, $payment->accountant_observation);
        $this->assertNotNull($payment->approved_at);

        // Verify event was dispatched
        Event::assertDispatched(PaymentCacheInvalidationEvent::class);
    }

    public function test_cancel_payment_handles_exception()
    {
        // Act as the authenticated admin
        $this->actingAs($this->admin);
        $payment = PaymentTransaction::factory()->create([
            'payable_id' => $this->user->id,
            'payable_type' => User::class,
            'status' => 'pending'
        ]);

        $this->transactionManager->shouldReceive('run')
            ->once()
            ->andThrow(new Exception('Database error'));

        $this->flasher->shouldReceive('crudFailure')
            ->once()
            ->with('payment.cancelled');

        $result = $this->paymentService->cancelPayment($payment);

        $this->assertFalse($result);
    }

    public function test_credit_coins_to_user_creates_coin_balance()
    {
        $payment = PaymentTransaction::factory()->create([
            'payable_id' => $this->user->id,
            'payable_type' => User::class,
            'coins_credited' => 150
        ]);

        $this->coinTransactionService->shouldReceive('createPurchasedTransaction')
            ->once()
            ->with(Mockery::type(User::class), 150);
        $paymentService = $this->getRealPaymentServiceInstance();
        // Use reflection to call private method
        $reflection = new \ReflectionClass($paymentService);
        $method = $reflection->getMethod('creditCoinsToUser');
        $method->setAccessible(true);
        $method->invoke($paymentService, $payment);

        // Verify coin balance was created and updated
        $coinBalance = CoinBalance::where('balanceable_id', $this->user->id)
            ->where('balanceable_type', User::class)
            ->first();

        $this->assertNotNull($coinBalance);
        $this->assertEquals(150, $coinBalance->balance);
    }

    public function test_credit_coins_to_user_updates_existing_coin_balance()
    {
        // conblance create on user create(by 0) observer
        $payment = PaymentTransaction::factory()->create([
            'payable_id' => $this->user->id,
            'payable_type' => User::class,
            'coins_credited' => 75
        ]);

        $this->coinTransactionService->shouldReceive('createPurchasedTransaction')
            ->once()
            ->with(Mockery::type(User::class), 75);

        // Use reflection to call private method
        $paymentService = $this->getRealPaymentServiceInstance();
        $reflection = new \ReflectionClass($paymentService);
        $method = $reflection->getMethod('creditCoinsToUser');
        $method->setAccessible(true);
        $method->invoke($paymentService, $payment);

        // Verify coin balance was updated
        $coinBalance = CoinBalance::where('balanceable_id', $this->user->id)
            ->where('balanceable_type', User::class)
            ->first();

        $this->assertNotNull($coinBalance);
        $this->assertEquals(75, $coinBalance->balance); // 75
    }

    public function test_get_coin_pricing_for_user()
    {
        $this->actingAs($this->user);
        // Create coin pricing for user type
        $userPricing = CoinPricing::factory()->create([
            'user_type' => 'user',
            'is_active' => true
        ]);

        $adminPricing = CoinPricing::factory()->create([
            'user_type' => 'admin',
            'is_active' => true
        ]);

        $universalPricing = CoinPricing::factory()->create([
            'user_type' => 'both',
            'is_active' => true
        ]);


        $result = $this->paymentService->getCoinPricingForUser();

        $this->assertCount(2, $result); // user + both
        $this->assertTrue($result->contains($userPricing));
        $this->assertTrue($result->contains($universalPricing));
        $this->assertFalse($result->contains($adminPricing));
    }

    public function test_create_payment_from_request_success()
    {
        // Arrange
        $this->actingAs($this->user);

        $coinPricing = CoinPricing::factory()->create(['user_type' => 'both']);

        // Mock uploaded file
        $uploadedFile = UploadedFile::fake()->image('proof.jpg', 500, 500);
        
        // Create request mock
        $request = new Request();
        $request->merge([
            'coin_pricing_id' => $coinPricing->id,
            'payment_method' => 'bank_transfer'
        ]);
        $request->files->set('proof_image', $uploadedFile);


        // Mock trait methods - this is the key part!
        $this->paymentService->shouldReceive('validateImage')
            ->once()
            ->with($uploadedFile, [
                'max_size' => 10240,
                'allowed_mimes' => ['jpeg', 'jpg', 'png', 'gif'],
                'min_width' => 100,
                'max_width' => 5000,
                'min_height' => 100,
                'max_height' => 5000,
            ])
            ->andReturn([]); // Return empty array (no errors)

        $this->paymentService->shouldReceive('saveImage')
            ->once()
            ->withArgs(function($file, $path, $options) use ($uploadedFile) {
                return $file === $uploadedFile 
                    && $path === config('image.private_types.transaction.path')
                    && is_array($options);
            })
            ->andReturn([
                'success' => true,
                'path' => 'transactions/proof_image_123.jpg'
            ]);

        // Mock coin pricing service
        $coins = 1000;
        $this->coinPricingService->shouldReceive('calculateCoins')
            ->once()
            ->with(Mockery::type(CoinPricing::class))
            ->andReturn($coins);

        // Mock transaction manager
        $this->transactionManager->shouldReceive('run')
            ->once()
            ->andReturnUsing(function($callback) {
                return $callback();
            });

        // Mock createPayment method (since it's called internally)
        $paymentTransaction = Mockery::mock(PaymentTransaction::class);
        $this->paymentService->shouldReceive('createPayment')
            ->once()
            ->with([
                'payable_id' => $this->user->id,
                'payable_type' => get_class($this->user),
                'amount' => $coinPricing->base_amount,
                'coins_credited' => $coins,
                'payment_method' => 'bank_transfer',
                'proof_image_path' => 'transactions/proof_image_123.jpg',
                'status' => 'pending',
            ])
            ->andReturn($paymentTransaction);

        // Mock flasher success message
        $this->flasher->shouldReceive('crudSuccess')
            ->once()
            ->with('saved');

        // Act
        $result = $this->paymentService->createPaymentFromRequest($request);

        // Assert
        $this->assertTrue($result);
    }

    public function test_create_payment_from_request_fail_invalid_image()
    {
        // Arrange
        $this->actingAs($this->user);

        $coinPricing = CoinPricing::factory()->create(['user_type' => 'both']);

        // Mock uploaded file
        $uploadedFile = UploadedFile::fake()->image('proof.jpg', 500, 500);
        
        // Create request mock
        $request = new Request();
        $request->merge([
            'coin_pricing_id' => $coinPricing->id,
            'payment_method' => 'bank_transfer'
        ]);
        $request->files->set('proof_image', $uploadedFile);


        // Mock trait methods - this is the key part!
        $errors[] = [
            'key' => 'file_size_exceeded',
            'params' => ['max_size' => 'error']
        ];
        $this->paymentService->shouldReceive('validateImage')
            ->once()
            ->with($uploadedFile, [
                'max_size' => 10240,
                'allowed_mimes' => ['jpeg', 'jpg', 'png', 'gif'],
                'min_width' => 100,
                'max_width' => 5000,
                'min_height' => 100,
                'max_height' => 5000,
            ])
            ->andReturn($errors); 
        
        $transmessages = ['error'];
        $this->paymentService->shouldReceive('translateImageErrors')
            ->once()
            ->with($errors)
            ->andReturn($transmessages); 

    
        // Mock transaction manager
        $this->transactionManager->shouldReceive('run')
            ->once()
            ->andReturnUsing(function($callback) {
                return $callback();
            });

        // Mock flasher success message
        $this->flasher->shouldReceive('error')
            ->once()
            ->with('error');

        // Act
        $result = $this->paymentService->createPaymentFromRequest($request);

        // Assert
        $this->assertFalse($result);
    }

    public function test_create_payment_from_request_fail_invalid_user_type()
    {
        // Arrange
        $this->actingAs($this->user);

        $coinPricing = CoinPricing::factory()->create(['user_type' => 'admin']);

        // Mock uploaded file
        $uploadedFile = UploadedFile::fake()->image('proof.jpg', 500, 500);
        
        // Create request mock
        $request = new Request();
        $request->merge([
            'coin_pricing_id' => $coinPricing->id,
            'payment_method' => 'bank_transfer'
        ]);
        $request->files->set('proof_image', $uploadedFile);


        // Mock trait methods - this is the key part!
        $this->paymentService->shouldReceive('validateImage')
            ->once()
            ->with($uploadedFile, [
                'max_size' => 10240,
                'allowed_mimes' => ['jpeg', 'jpg', 'png', 'gif'],
                'min_width' => 100,
                'max_width' => 5000,
                'min_height' => 100,
                'max_height' => 5000,
            ])
            ->andReturn([]); // Return empty array (no errors)

        // Mock transaction manager
        $this->transactionManager->shouldReceive('run')
            ->once()
            ->andReturnUsing(function($callback) {
                return $callback();
            });

        // Mock flasher success message
        $this->flasher->shouldReceive('error')
            ->once()
            ->with(__('messages.validation.not_allow.transcation_incorrect_user_type'));

        // Act
        $result = $this->paymentService->createPaymentFromRequest($request);

        // Assert
        $this->assertFalse($result);
    }

    public function test_create_payment_from_request_handles_exception()
    {
        // Mock request
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('file')->with('proof_image')->andReturn(null);

        $this->transactionManager->shouldReceive('run')
            ->once()
            ->andThrow(new Exception('Database error'));

        $this->flasher->shouldReceive('crudFailure')
            ->once()
            ->with('saved');

        $result = $this->paymentService->createPaymentFromRequest($request);

        $this->assertFalse($result);
    }


    private function getRealPaymentServiceInstance(): PaymentService
    {
        return new PaymentService(
            $this->transactionManager,
            $this->flasher,
            $this->coinPricingService,
            $this->notificationService,
            $this->coinTransactionService
        );
    }
} 