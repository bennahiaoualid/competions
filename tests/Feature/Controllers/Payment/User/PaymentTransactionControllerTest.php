<?php

namespace Tests\Feature\Controllers\Payment\User;

use Bus;
use Tests\TestCase;
use App\Models\User;
use App\Models\Admin\Admin;
use App\Models\SystemSetting;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use App\Models\Payment\CoinBalance;
use App\Models\Payment\CoinPricing;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use App\Models\Payment\PaymentTransaction;
use App\Jobs\Notifications\BatchBroadcastJob;
use App\Jobs\Notifications\BatchNotificationJob;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaymentTransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Admin $admin;
    protected Admin $accountant;
    protected CoinPricing $coinPricing;
    protected PaymentTransaction $paymentTransaction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->refreshApplicationWithLocale('en');

        // Seed roles and permissions
        $this->seed(RoleSeeder::class);

        // Create test users
        $this->user = User::factory()->create();
        $this->admin = Admin::factory()->create();
        $this->admin->assignRole('owner');

        $this->accountant = Admin::factory()->create();
        $this->accountant->assignRole('accountant');

        // Create coin pricing
        $this->coinPricing = CoinPricing::factory()->create([
            'user_type' => 'user',
            'base_amount' => 100,
            'base_coins' => 1000,
            'is_active' => true
        ]);

        // Create payment transaction
        $this->paymentTransaction = PaymentTransaction::factory()->create([
            'payable_id' => $this->user->id,
            'payable_type' => User::class,
            'status' => 'pending',
            'amount' => 100.00,
            'coins_credited' => 1000,
            'payment_method' => 'bank_transfer'
        ]);

        // Fake storage for file uploads
        Storage::fake('local');
        Bus::fake();
    }

    // ========================================
    // AUTHENTICATION TESTS
    // ========================================

    public function test_unauthenticated_users_cannot_access_payment_routes()
    {
        $routes = [
            ['get', route('payment.create')],
            ['post', route('payment.store')],
            ['get', route('payment.transactions')],
            ['get', route('payment.transactions.show', ['paymentTransaction' => $this->paymentTransaction->uuid])],
            ['get', route('payment.coin-balance')],
            ['post', route('payment.reviews.order')],
        ];

        foreach ($routes as [$method, $uri]) {
            $response = $this->{$method}($uri);
            $response->assertRedirect(route('login'));
        }
    }

    // ========================================
    // INDEX METHOD TESTS
    // ========================================

    public function test_authenticated_user_can_view_payment_transactions()
    {
        $this->actingAs($this->user, 'web');

        $response = $this->get(route('payment.transactions'));

        $response->assertOk();
        $response->assertViewIs('pages.payment.transactions');
        $response->assertViewHas('transactions');
        $response->assertViewHas('statusCounts');
    }

    // ========================================
    // SHOW METHOD TESTS
    // ========================================

    public function test_user_can_view_own_payment_transaction()
    {
        $this->actingAs($this->user, 'web');

        $response = $this->get(route('payment.transactions.show', ['paymentTransaction' => $this->paymentTransaction->uuid]));

        $response->assertOk();
        $response->assertViewIs('pages.payment.show');
        $response->assertViewHas('paymentTransaction');
        $response->assertViewHas('canOrderReview');
        $response->assertViewHas('review');
    }

    public function test_user_cannot_view_other_user_transaction()
    {
        $otherUser = User::factory()->create();
        $otherTransaction = PaymentTransaction::factory()->create([
            'payable_id' => $otherUser->id,
            'payable_type' => User::class,
        ]);

        $this->actingAs($this->user, 'web');

        $response = $this->get(route('payment.transactions.show', ['paymentTransaction' => $otherTransaction->uuid]));

        $response->assertStatus(403);
    }

    public function test_returns_404_for_nonexistent_transaction()
    {
        $this->actingAs($this->user, 'web');

        $response = $this->get(route('payment.transactions.show', ['paymentTransaction' => 'nonexistent-uuid']));

        $response->assertStatus(404);
    }

    // ========================================
    // CREATE METHOD TESTS
    // ========================================

    public function test_authenticated_user_can_access_payment_creation_form()
    {
        $this->actingAs($this->user, 'web');

        $response = $this->get(route('payment.create'));

        $response->assertOk();
        $response->assertViewIs('pages.payment.create');
        $response->assertViewHas('coinPricing');
    }

    // ========================================
    // STORE METHOD TESTS
    // ========================================

    public function test_user_can_create_payment_transaction_with_valid_data()
    {
        $this->actingAs($this->user, 'web');
        $this->seed(\Database\Seeders\SystemSettingSeeder::class);
        // Ensure system setting exists with a low limit
        $systemSetting = SystemSetting::updateOrCreate(
            [
                'setting_key' => 'max_daily_transactions'
            ],
            [
                'setting_value' => '2',
                'setting_trans_key' => 'settings.payment.max_daily_transactions'
            ]
        );
        $file = UploadedFile::fake()->image('payment_proof.jpg', 200, 200);

        $paymentData = [
            'coin_pricing_id' => $this->coinPricing->id,
            'payment_method' => 'bank_transfer',
            'proof_image' => $file
        ];

        $response = $this->post(route('payment.store'), $paymentData);

        $response->assertRedirectBack();
        
        $this->assertDatabaseHas('payment_transactions', [
            'payable_id' => $this->user->id,
            'payable_type' => User::class,
            'amount' => $this->coinPricing->base_amount,
            'coins_credited' => $this->coinPricing->base_coins,
            'status' => 'pending',
            'payment_method' => 'bank_transfer'
        ]);

        Bus::assertDispatched(BatchNotificationJob::class);
        Bus::assertDispatched(BatchBroadcastJob::class);
    }

    public function test_user_cannot_create_payment_with_invalid_data()
    {
        $this->actingAs($this->user, 'web');

        $invalidData = [
            'coin_pricing_id' => '', // required
            'payment_method' => 'invalid_method', // not in allowed values
            'proof_image' => '' // required
        ];

        $response = $this->post(route('payment.store'), $invalidData);

        $response->assertSessionHasErrors(['coin_pricing_id', 'payment_method', 'proof_image'], errorBag: 'storePaymentTransaction');
    }

    public function test_user_cannot_exceed_daily_transaction_limit()
    {
        $this->actingAs($this->user, 'web');
         // Seed system settings
        $this->seed(\Database\Seeders\SystemSettingSeeder::class);
        // Ensure system setting exists with a low limit
        $systemSetting = SystemSetting::updateOrCreate(
            [
                'setting_key' => 'max_daily_transactions'
            ],
            [
                'setting_value' => '1',
                'setting_trans_key' => 'settings.payment.max_daily_transactions'
            ]
        );

        // Clear any cached settings to ensure fresh value
        $systemSettingService = app(\App\Services\SystemSettingService::class);
        $systemSettingService->clearCache();
        
        // Verify the setting value is correct
        $maxTransactions = $systemSettingService->getValueAsInt('max_daily_transactions');
        $this->assertEquals(1, $maxTransactions, 'System setting should return 1 for max_daily_transactions');

        // Create first transaction for this user (this should hit the limit)
        PaymentTransaction::factory()->create([
            'payable_id' => $this->user->id,
            'payable_type' => User::class,
            'amount' => 100.00,
            'coins_credited' => 1000,
            'payment_method' => 'bank_transfer',
            'created_at' => now()
        ]);

        $file = UploadedFile::fake()->image('payment_proof.jpg');

        $paymentData = [
            'coin_pricing_id' => $this->coinPricing->id,
            'payment_method' => 'bank_transfer',
            'proof_image' => $file
        ];

        $response = $this->post(route('payment.store'), $paymentData);

        $response->assertRedirectBack();
        
        // Verify the setting was created correctly
        $this->assertDatabaseHas('system_settings', [
            'setting_key' => 'max_daily_transactions',
            'setting_value' => '1'
        ]);
        
        // Check the error message
        $this->assertStringContainsString(
            session()->get('messages')[0]['message'],
            __('messages.validation.not_allow.max_daily_transactions_reached', ['number' => 1])
        );
    }

    // ========================================
    // ORDER REVIEW TESTS
    // ========================================

    public function test_user_can_order_review_for_rejected_transaction()
    {
        $rejectedTransaction = PaymentTransaction::factory()->create([
            'payable_id' => $this->user->id,
            'payable_type' => User::class,
            'amount' => 100.00,
            'coins_credited' => 1000,
            'payment_method' => 'bank_transfer',
            'status' => 'rejected',
            'approved_at' => now()->subHours(40)
        ]);

        $rejectedTransactionTimePass = PaymentTransaction::factory()->create([
            'payable_id' => $this->user->id,
            'payable_type' => User::class,
            'amount' => 100.00,
            'coins_credited' => 1000,
            'payment_method' => 'bank_transfer',
            'status' => 'rejected',
            'approved_at' => now()->subHours(49)
        ]);

        $this->actingAs($this->user, 'web');

        $reviewData = [
            'transaction_id' => $rejectedTransaction->id,
            'reason' => 'I believe this was incorrectly rejected'
        ];

        $response = $this->post(route('payment.reviews.order'), $reviewData);

        $response->assertRedirectBack();
        
        $this->assertDatabaseHas('payment_review_requests', [
            'payment_transaction_id' => $rejectedTransaction->id,
            'request_reason' => 'I believe this was incorrectly rejected'
        ]);
        session()->flush();

        $reviewData = [
            'transaction_id' => $rejectedTransactionTimePass->id,
            'reason' => 'I believe this was incorrectly rejected'
        ];

        $response = $this->post(route('payment.reviews.order'), $reviewData);

        $response->assertRedirectBack();
        
        $this->assertDatabaseMissing('payment_review_requests', [
            'payment_transaction_id' => $rejectedTransactionTimePass->id,
            'request_reason' => 'I believe this was incorrectly rejected'
        ]);
        $this->assertStringContainsString(
            session()->get('messages')[0]['message'],
            __('payment.review.messages.review_period_passed')
        );
        Bus::assertDispatched(BatchNotificationJob::class);
        Bus::assertDispatched(BatchBroadcastJob::class);
    }

    public function test_user_cannot_order_review_for_approved_transaction()
    {
        $approvedTransaction = PaymentTransaction::factory()->create([
            'payable_id' => $this->user->id,
            'payable_type' => User::class,
            'amount' => 100.00,
            'coins_credited' => 1000,
            'payment_method' => 'bank_transfer',
            'status' => 'approved',
            'approved_at' => now()
        ]);

        $this->actingAs($this->user, 'web');

        $reviewData = [
            'transaction_id' => $approvedTransaction->id,
            'reason' => 'This should not work'
        ];

        $response = $this->post(route('payment.reviews.order'), $reviewData);

        $response->assertStatus(403);
    }

    public function test_user_cannot_order_review_for_other_user_transaction()
    {
        $otherUser = User::factory()->create();
        $otherTransaction = PaymentTransaction::factory()->create([
            'payable_id' => $otherUser->id,
            'payable_type' => User::class,
            'amount' => 100.00,
            'coins_credited' => 1000,
            'payment_method' => 'bank_transfer',
            'status' => 'rejected'
        ]);

        $this->actingAs($this->user, 'web');

        $reviewData = [
            'transaction_id' => $otherTransaction->id,
            'reason' => 'This should not work'
        ];

        $response = $this->post(route('payment.reviews.order'), $reviewData);

        $response->assertStatus(403);
    }

    public function test_user_cannot_order_review_with_invalid_data()
    {
        $this->actingAs($this->user, 'web');

        $invalidData = [
            'transaction_id' => '', // required
            'reason' => '' // required
        ];

        $response = $this->post(route('payment.reviews.order'), $invalidData);

        $response->assertSessionHasErrors(['transaction_id', 'reason'], errorBag: 'orderReview');
    }

    public function test_user_cannot_order_review_for_nonexistent_transaction()
    {
        $this->actingAs($this->user, 'web');

        $reviewData = [
            'transaction_id' => 99999, // nonexistent
            'reason' => 'This should not work'
        ];

        $response = $this->post(route('payment.reviews.order'), $reviewData);

        $response->assertStatus(404);
    }

    // ========================================
    // EDGE CASES
    // ========================================

    public function test_admin_can_access_payment_routes_with_admin_guard()
    {
        $this->actingAs($this->admin, 'admin');

        $response = $this->get(route('payment.create'));
        $response->assertOk();

        $response = $this->get(route('payment.transactions'));
        $response->assertOk();
    }

    public function test_accountant_can_not_access_payment_routes()
    {
        $this->actingAs($this->accountant, 'admin');

        $routes = [
            ['get', route('payment.create')],
            ['get', route('payment.transactions')],
            ['get', route('payment.transactions.show', ['paymentTransaction' => $this->paymentTransaction->uuid])],
            ['post', route('payment.reviews.order')],
        ];

        foreach ($routes as [$method, $uri]) {
            $response = $this->{$method}($uri);
            $response->assertStatus(403);
        }
    }
} 