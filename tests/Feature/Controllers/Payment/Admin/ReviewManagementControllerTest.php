<?php

namespace Tests\Feature\Controllers\Payment\Admin;

use Tests\TestCase;
use App\Models\Admin\Admin;
use App\Models\Payment\PaymentReviewRequest;
use App\Models\Payment\PaymentTransaction;
use App\Models\User;
use App\Jobs\Notifications\BatchPaymentNotificationJob;
use App\Jobs\Notifications\BatchPaymentBroadcastJob;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

class ReviewManagementControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $owner;
    protected Admin $accountant;
    protected Admin $admin;
    protected PaymentReviewRequest $reviewRequest;
    protected PaymentTransaction $paymentTransaction;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->refreshApplicationWithLocale('en');

        // Seed roles and permissions
        $this->seed(RoleSeeder::class);

        // Create test admins with different roles
        $this->owner = Admin::factory()->create();
        $this->owner->assignRole('owner');

        $this->accountant = Admin::factory()->create();
        $this->accountant->assignRole('accountant');

        $this->admin = Admin::factory()->create();
        $this->admin->assignRole('super_admin');

        // Create test user and payment transaction
        $this->user = User::factory()->create();
        $this->paymentTransaction = PaymentTransaction::factory()->create([
            'payable_id' => $this->user->id,
            'payable_type' => User::class,
            'amount' => 100,
            'coins_credited' => 1000,
            'payment_method' => 'bank_transfer',
            'status' => 'pending'
        ]);

        // Create payment review request for testing
        $this->reviewRequest = PaymentReviewRequest::factory()->create([
            'payment_transaction_id' => $this->paymentTransaction->id,
            'request_reason' => 'Payment proof unclear',
            'status' => 'pending'
        ]);

        // Fake the bus to prevent actual job execution
        Bus::fake();
    }

    // ========================================
    // AUTHENTICATION TESTS
    // ========================================

    public function test_unauthenticated_users_cannot_access_payment_review_routes()
    {
        $routes = [
            ['get', route('admin.payment.reviews.index')],
            ['post', route('admin.payment.reviews.approve')],
            ['post', route('admin.payment.reviews.reject')],
        ];

        foreach ($routes as [$method, $uri]) {
            $response = $this->{$method}($uri);
            $response->assertRedirect(route('login'));
        }
    }

    // ========================================
    // INDEX METHOD TESTS
    // ========================================

    public function test_owner_can_view_payment_reviews()
    {
        $this->actingAs($this->owner, 'admin');

        $response = $this->get(route('admin.payment.reviews.index'));

        $response->assertOk();
        $response->assertViewIs('pages.admin.payment.reviews');
    }

    public function test_accountant_can_view_payment_reviews()
    {
        $this->actingAs($this->accountant, 'admin');

        $response = $this->get(route('admin.payment.reviews.index'));

        $response->assertOk();
        $response->assertViewIs('pages.admin.payment.reviews');
    }

    public function test_regular_admin_cannot_view_payment_reviews()
    {
        $this->actingAs($this->admin, 'admin');

        $response = $this->get(route('admin.payment.reviews.index'));

        $response->assertStatus(403);
    }

    // ========================================
    // APPROVE METHOD TESTS
    // ========================================

    public function test_owner_cannot_approve_payment_review()
    {
        $this->actingAs($this->owner, 'admin');

        $reviewToApprove = PaymentReviewRequest::factory()->create([
            'payment_transaction_id' => $this->paymentTransaction->id,
            'request_reason' => 'Review to approve',
            'status' => 'pending'
        ]);

        $response = $this->post(route('admin.payment.reviews.approve'), [
            'review_id' => $reviewToApprove->id,
            'observation' => 'Payment proof is clear and valid'
        ]);

        $response->assertStatus(403);
        
        $this->assertDatabaseHas('payment_review_requests', [
            'id' => $reviewToApprove->id,
            'status' => 'pending'
        ]);

        $this->assertDatabaseHas('payment_transactions', [
            'id' => $this->paymentTransaction->id,
            'status' => 'pending'
        ]);

        // Assert that no notification jobs were dispatched
        Bus::assertNotDispatched(BatchPaymentNotificationJob::class);
       
    }

    public function test_accountant_with_manage_payment_permission_can_approve_payment_review()
    {
        $this->actingAs($this->accountant, 'admin');

        $reviewToApprove = PaymentReviewRequest::factory()->create([
            'payment_transaction_id' => $this->paymentTransaction->id,
            'request_reason' => 'Review to approve by accountant',
            'status' => 'pending'
        ]);

        $response = $this->post(route('admin.payment.reviews.approve'), [
            'review_id' => $reviewToApprove->id,
            'observation' => 'Payment proof verified by accountant'
        ]);

        $response->assertRedirectBack();
        
        $this->assertDatabaseHas('payment_review_requests', [
            'id' => $reviewToApprove->id,
            'status' => 'approved',
            'reviewed_by_admin_id' => $this->accountant->id,
            'review_observation' => 'Payment proof verified by accountant'
        ]);

        $this->assertDatabaseHas('payment_transactions', [
            'id' => $this->paymentTransaction->id,
            'status' => 'approved'
        ]);
        
        $this->assertNotNull($reviewToApprove->fresh()->reviewed_at);

        // Assert that notification jobs were dispatched
        Bus::assertDispatched(BatchPaymentNotificationJob::class);
    }

    public function test_regular_admin_cannot_approve_payment_review()
    {
        $this->actingAs($this->admin, 'admin');

        $reviewToApprove = PaymentReviewRequest::factory()->create([
            'payment_transaction_id' => $this->paymentTransaction->id,
            'request_reason' => 'Review to approve by admin',
            'status' => 'pending'
        ]);

        $response = $this->post(route('admin.payment.reviews.approve'), [
            'review_id' => $reviewToApprove->id,
            'observation' => 'This should not work'
        ]);

        $response->assertStatus(403);
        
        // Review should remain pending
        $this->assertDatabaseHas('payment_review_requests', [
            'id' => $reviewToApprove->id,
            'status' => 'pending'
        ]);

        $this->assertDatabaseHas('payment_transactions', [
            'id' => $this->paymentTransaction->id,
            'status' => 'pending'
        ]);

        // Assert that no notification jobs were dispatched
        Bus::assertNotDispatched(BatchPaymentNotificationJob::class);
       
    }

    // ========================================
    // REJECT METHOD TESTS
    // ========================================

    public function test_owner_cannot_reject_payment_review()
    {
        $this->actingAs($this->owner, 'admin');

        $reviewToReject = PaymentReviewRequest::factory()->create([
            'payment_transaction_id' => $this->paymentTransaction->id,
            'request_reason' => 'Review to reject',
            'status' => 'pending'
        ]);

        $response = $this->post(route('admin.payment.reviews.reject'), [
            'review_id' => $reviewToReject->id,
            'observation' => 'Payment proof is insufficient'
        ]);

        $response->assertStatus(403);
        
        $this->assertDatabaseHas('payment_review_requests', [
            'id' => $reviewToReject->id,
            'status' => 'pending'
        ]);

        // Assert that no notification jobs were dispatched
        Bus::assertNotDispatched(BatchPaymentNotificationJob::class);
    }

    public function test_accountant_with_manage_payment_permission_can_reject_payment_review()
    {
        $this->actingAs($this->accountant, 'admin');

        $reviewToReject = PaymentReviewRequest::factory()->create([
            'payment_transaction_id' => $this->paymentTransaction->id,
            'request_reason' => 'Review to reject by accountant',
            'status' => 'pending'
        ]);

        $response = $this->post(route('admin.payment.reviews.reject'), [
            'review_id' => $reviewToReject->id,
            'observation' => 'Payment proof rejected by accountant'
        ]);

        $response->assertRedirectBack();
        
        $this->assertDatabaseHas('payment_review_requests', [
            'id' => $reviewToReject->id,
            'status' => 'rejected',
            'reviewed_by_admin_id' => $this->accountant->id,
            'review_observation' => 'Payment proof rejected by accountant'
        ]);
        
        $this->assertNotNull($reviewToReject->fresh()->reviewed_at);

        // Assert that notification jobs were dispatched
        Bus::assertDispatched(BatchPaymentNotificationJob::class);
    }

    public function test_regular_admin_cannot_reject_payment_review()
    {
        $this->actingAs($this->admin, 'admin');

        $reviewToReject = PaymentReviewRequest::factory()->create([
            'payment_transaction_id' => $this->paymentTransaction->id,
            'request_reason' => 'Review to reject by admin',
            'status' => 'pending'
        ]);

        $response = $this->post(route('admin.payment.reviews.reject'), [
            'review_id' => $reviewToReject->id,
            'observation' => 'This should not work'
        ]);

        $response->assertStatus(403);
        
        // Review should remain pending
        $this->assertDatabaseHas('payment_review_requests', [
            'id' => $reviewToReject->id,
            'status' => 'pending'
        ]);

        // Assert that no notification jobs were dispatched
        Bus::assertNotDispatched(BatchPaymentNotificationJob::class);
       
    }

    // ========================================
    // VALIDATION TESTS
    // ========================================

    public function test_approve_review_requires_review_id()
    {
        $this->actingAs($this->accountant, 'admin');

        $response = $this->post(route('admin.payment.reviews.approve'), [
            'review_id' => '', // Missing required field
            'observation' => 'Payment proof is valid'
        ]);

        $response->assertSessionHasErrors(['review_id'], errorBag: 'approveReview');

        // Assert that no notification jobs were dispatched
        Bus::assertNotDispatched(BatchPaymentNotificationJob::class);
       
    }


    public function test_reject_review_requires_review_id()
    {
        $this->actingAs($this->accountant, 'admin');

        $response = $this->post(route('admin.payment.reviews.reject'), [
            'review_id' => '', // Missing required field
            'observation' => 'Payment proof is insufficient'
        ]);

        $response->assertSessionHasErrors(['review_id'], errorBag: 'rejectReview');

        // Assert that no notification jobs were dispatched
        Bus::assertNotDispatched(BatchPaymentNotificationJob::class);
       
    }

    public function test_reject_review_requires_observation()
    {
        $this->actingAs($this->accountant, 'admin');

        $response = $this->post(route('admin.payment.reviews.reject'), [
            'review_id' => $this->reviewRequest->id,
            'observation' => '' // Missing required field
        ]);

        $response->assertSessionHasErrors(['observation'], errorBag: 'rejectReview');

        // Assert that no notification jobs were dispatched
        Bus::assertNotDispatched(BatchPaymentNotificationJob::class);
       
    }

    public function test_review_id_must_be_integer()
    {
        $this->actingAs($this->accountant, 'admin');

        $response = $this->post(route('admin.payment.reviews.approve'), [
            'review_id' => 'invalid_id', // Non-integer value
            'observation' => 'Payment proof is valid'
        ]);

        $response->assertSessionHasErrors(['review_id'], errorBag: 'approveReview');

        // Assert that no notification jobs were dispatched
        Bus::assertNotDispatched(BatchPaymentNotificationJob::class);
       
    }

    public function test_observation_must_be_string()
    {
        $this->actingAs($this->accountant, 'admin');

        $response = $this->post(route('admin.payment.reviews.approve'), [
            'review_id' => $this->reviewRequest->id,
            'observation' => 12345 // Non-string value
        ]);

        $response->assertSessionHasErrors(['observation'], errorBag: 'approveReview');

        // Assert that no notification jobs were dispatched
        Bus::assertNotDispatched(BatchPaymentNotificationJob::class);
       
    }

    public function test_observation_has_minimum_length()
    {
        $this->actingAs($this->accountant, 'admin');

        $response = $this->post(route('admin.payment.reviews.approve'), [
            'review_id' => $this->reviewRequest->id,
            'observation' => 'ab' // Too short
        ]);

        $response->assertSessionHasErrors(['observation'], errorBag: 'approveReview');

        // Assert that no notification jobs were dispatched
        Bus::assertNotDispatched(BatchPaymentNotificationJob::class);
       
    }

    public function test_observation_has_maximum_length()
    {
        $this->actingAs($this->accountant, 'admin');

        $longObservation = str_repeat('a', 1001); // Too long

        $response = $this->post(route('admin.payment.reviews.approve'), [
            'review_id' => $this->reviewRequest->id,
            'observation' => $longObservation
        ]);

        $response->assertSessionHasErrors(['observation'], errorBag: 'approveReview');

        // Assert that no notification jobs were dispatched
        Bus::assertNotDispatched(BatchPaymentNotificationJob::class);
       
    }

    // ========================================
    // EDGE CASES
    // ========================================

    public function test_cannot_approve_already_approved_review()
    {
        $this->actingAs($this->accountant, 'admin');

        $approvedReview = PaymentReviewRequest::factory()->create([
            'payment_transaction_id' => $this->paymentTransaction->id,
            'request_reason' => 'Already approved review',
            'status' => 'approved',
            'reviewed_by_admin_id' => $this->owner->id,
            'reviewed_at' => now()->subHour(),
            'review_observation' => 'Previous approval'
        ]);

        $response = $this->post(route('admin.payment.reviews.approve'), [
            'review_id' => $approvedReview->id,
            'observation' => 'Trying to approve again'
        ]);

        $response->assertRedirectBack();
        
        // Status should remain approved, not change
        $this->assertDatabaseHas('payment_review_requests', [
            'id' => $approvedReview->id,
            'status' => 'approved',
            'reviewed_by_admin_id' => $this->owner->id,
            'review_observation' => 'Previous approval'
        ]);

        // Assert that no notification jobs were dispatched
        Bus::assertNotDispatched(BatchPaymentNotificationJob::class);
       
    }

    public function test_cannot_reject_already_rejected_review()
    {
        $this->actingAs($this->accountant, 'admin');

        $rejectedReview = PaymentReviewRequest::factory()->create([
            'payment_transaction_id' => $this->paymentTransaction->id,
            'request_reason' => 'Already rejected review',
            'status' => 'rejected',
            'reviewed_by_admin_id' => $this->owner->id,
            'reviewed_at' => now()->subHour(),
            'review_observation' => 'Previous rejection'
        ]);

        $response = $this->post(route('admin.payment.reviews.reject'), [
            'review_id' => $rejectedReview->id,
            'observation' => 'Trying to reject again'
        ]);

        $response->assertRedirectBack();
        
        // Status should remain rejected, not change
        $this->assertDatabaseHas('payment_review_requests', [
            'id' => $rejectedReview->id,
            'status' => 'rejected',
            'reviewed_by_admin_id' => $this->owner->id,
            'review_observation' => 'Previous rejection'
        ]);

        // Assert that no notification jobs were dispatched
        Bus::assertNotDispatched(BatchPaymentNotificationJob::class);
       
    }

    public function test_cannot_approve_nonexistent_review()
    {
        $this->actingAs($this->accountant, 'admin');

        $response = $this->post(route('admin.payment.reviews.approve'), [
            'review_id' => 99999,
            'observation' => 'Trying to approve nonexistent review'
        ]);

        $response->assertStatus(404);

        // Assert that no notification jobs were dispatched
        Bus::assertNotDispatched(BatchPaymentNotificationJob::class);
       
    }

    public function test_cannot_reject_nonexistent_review()
    {
        $this->actingAs($this->accountant, 'admin');

        $response = $this->post(route('admin.payment.reviews.reject'), [
            'review_id' => 99999,
            'observation' => 'Trying to reject nonexistent review'
        ]);

        $response->assertStatus(404);

        // Assert that no notification jobs were dispatched
        Bus::assertNotDispatched(BatchPaymentNotificationJob::class);
       
    }

    public function test_can_approve_review_with_maximum_observation_length()
    {
        $this->actingAs($this->accountant, 'admin');

        $reviewToApprove = PaymentReviewRequest::factory()->create([
            'payment_transaction_id' => $this->paymentTransaction->id,
            'request_reason' => 'Review with maximum observation',
            'status' => 'pending'
        ]);

        $maxObservation = str_repeat('a', 1000); // Maximum length

        $response = $this->post(route('admin.payment.reviews.approve'), [
            'review_id' => $reviewToApprove->id,
            'observation' => $maxObservation
        ]);

        $response->assertRedirectBack();
        
        $this->assertDatabaseHas('payment_review_requests', [
            'id' => $reviewToApprove->id,
            'status' => 'approved',
            'review_observation' => $maxObservation
        ]);
    }

    public function test_approve_updates_reviewed_at_timestamp()
    {
        $this->actingAs($this->accountant, 'admin');

        $reviewToApprove = PaymentReviewRequest::factory()->create([
            'payment_transaction_id' => $this->paymentTransaction->id,
            'request_reason' => 'Review to test timestamp',
            'status' => 'pending'
        ]);

        $beforeApproval = now()->subMinute();
        
        $response = $this->post(route('admin.payment.reviews.approve'), [
            'review_id' => $reviewToApprove->id,
            'observation' => 'Testing timestamp update'
        ]);

        $response->assertRedirectBack();
        
        $reviewedAt = $reviewToApprove->fresh()->reviewed_at;
        $this->assertNotNull($reviewedAt);
        $this->assertGreaterThan($beforeApproval, $reviewedAt);
    }

    public function test_reject_updates_reviewed_at_timestamp()
    {
        $this->actingAs($this->accountant, 'admin');

        $reviewToReject = PaymentReviewRequest::factory()->create([
            'payment_transaction_id' => $this->paymentTransaction->id,
            'request_reason' => 'Review to test reject timestamp',
            'status' => 'pending'
        ]);

        $beforeRejection = now()->subMinute();
        
        $response = $this->post(route('admin.payment.reviews.reject'), [
            'review_id' => $reviewToReject->id,
            'observation' => 'Testing reject timestamp update'
        ]);

        $response->assertRedirectBack();
        
        $reviewedAt = $reviewToReject->fresh()->reviewed_at;
        $this->assertNotNull($reviewedAt);
        $this->assertGreaterThan($beforeRejection, $reviewedAt);
    }
} 