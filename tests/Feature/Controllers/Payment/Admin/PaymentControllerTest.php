<?php

namespace Tests\Feature\Controllers\Payment\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Admin\Admin;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use App\Models\Payment\PaymentTransaction;
use App\Jobs\Notifications\BatchBroadcastJob;
use App\Jobs\Notifications\BatchNotificationJob;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected Admin $owner;
    protected Admin $superAdmin;
    protected Admin $accountant;
    protected User $user;
    protected PaymentTransaction $paymentTransaction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->refreshApplicationWithLocale('en');

        // Seed roles and permissions
        $this->seed(RoleSeeder::class);

        // Create test admins with different roles
        $this->owner = Admin::factory()->create();
        $this->owner->assignRole('owner');

        $this->superAdmin = Admin::factory()->create();
        $this->superAdmin->assignRole('super_admin');

        $this->admin = Admin::factory()->create();
        $this->admin->assignRole('manager');

        $this->accountant = Admin::factory()->create();
        $this->accountant->assignRole('accountant');

        // Create a regular user
        $this->user = User::factory()->create();

        // Create payment transaction
        $this->paymentTransaction = PaymentTransaction::factory()->create([
            'payable_id' => $this->user->id,
            'payable_type' => User::class,
            'status' => 'pending',
            'amount' => 100.00,
            'coins_credited' => 1000,
            'payment_method' => 'bank_transfer'
        ]);

        // Fake queues for job testing
        Bus::fake();
        Queue::fake();
    }

    // ========================================
    // AUTHENTICATION TESTS
    // ========================================

    public function test_unauthenticated_users_cannot_access_admin_payment_routes()
    {
        $routes = [
            ['get', route('admin.payment.transactions')],
            ['post', route('admin.payment.approve')],
            ['post', route('admin.payment.reject')],
            ['post', route('admin.payment.cancel')],
        ];

        foreach ($routes as [$method, $uri]) {
            $response = $this->{$method}($uri);
            $response->assertRedirect(route('login'));
        }
    }

    // ========================================
    // TRANSACTIONS VIEW TESTS
    // ========================================

    public function test_admin_with_view_payment_permission_can_view_transactions()
    {
        $this->actingAs($this->accountant, 'admin');

        $response = $this->get(route('admin.payment.transactions'));

        $response->assertOk();
        $response->assertViewIs('pages.admin.payment.transactions');
    }

    public function test_admin_without_view_payment_permission_cannot_view_transactions()
    {
        $this->actingAs($this->admin, 'admin'); // manager role without payment permissions

        $response = $this->get(route('admin.payment.transactions'));

        $response->assertStatus(403);
    }

    // ========================================
    // APPROVE PAYMENT TESTS
    // ========================================

    public function test_admin_with_manage_payment_permission_can_approve_payment()
    {
        $this->actingAs($this->accountant, 'admin');

        $approveData = [
            'transaction_id' => $this->paymentTransaction->uuid,
            'observation' => 'Payment approved after verification'
        ];

        $response = $this->post(route('admin.payment.approve'), $approveData);

        $response->assertRedirectBack();
        
        // Verify the transaction was updated
        $this->assertDatabaseHas('payment_transactions', [
            'id' => $this->paymentTransaction->id,
            'status' => 'approved'
        ]);

        Bus::assertDispatched(BatchNotificationJob::class);
    }

    public function test_admin_without_manage_payment_permission_cannot_approve_payment()
    {
        $this->actingAs($this->admin, 'admin'); // manager role without payment permissions

        $approveData = [
            'transaction_id' => $this->paymentTransaction->uuid,
            'observation' => 'This should not work'
        ];

        $response = $this->post(route('admin.payment.approve'), $approveData);

        $response->assertStatus(403);
    }

    public function test_approve_payment_without_observation()
    {
        $this->actingAs($this->accountant, 'admin');

        $approveData = [
            'transaction_id' => $this->paymentTransaction->uuid,
            'observation' => null
        ];

        $response = $this->post(route('admin.payment.approve'), $approveData);

        $response->assertRedirectBack();
        
        $this->assertDatabaseHas('payment_transactions', [
            'id' => $this->paymentTransaction->id,
            'status' => 'approved'
        ]);
    }

    // ========================================
    // REJECT PAYMENT TESTS
    // ========================================

    public function test_admin_with_manage_payment_permission_can_reject_payment()
    {
        $this->actingAs($this->accountant, 'admin');

        $rejectData = [
            'transaction_id' => $this->paymentTransaction->uuid,
            'observation' => 'Payment rejected - insufficient proof'
        ];

        $response = $this->post(route('admin.payment.reject'), $rejectData);

        $response->assertRedirectBack();
        
        $this->assertDatabaseHas('payment_transactions', [
            'id' => $this->paymentTransaction->id,
            'status' => 'rejected'
        ]);

        Bus::assertDispatched(BatchNotificationJob::class);
        Bus::assertDispatched(BatchBroadcastJob::class);
    }

    public function test_admin_without_manage_payment_permission_cannot_reject_payment()
    {
        $this->actingAs($this->admin, 'admin'); // manager role without payment permissions

        $rejectData = [
            'transaction_id' => $this->paymentTransaction->uuid,
            'observation' => 'This should not work'
        ];

        $response = $this->post(route('admin.payment.reject'), $rejectData);

        $response->assertStatus(403);
    }

    public function test_reject_payment_requires_observation()
    {
        $this->actingAs($this->accountant, 'admin');

        $rejectData = [
            'transaction_id' => $this->paymentTransaction->uuid,
            'observation' => '' // Empty observation should fail validation
        ];

        $response = $this->post(route('admin.payment.reject'), $rejectData);

        $response->assertSessionHasErrors(['observation'], errorBag: 'rejectPayment');
    }

    // ========================================
    // CANCEL PAYMENT TESTS
    // ========================================

    public function test_admin_with_manage_payment_permission_can_cancel_payment()
    {
        $this->actingAs($this->accountant, 'admin');

        $cancelData = [
            'transaction_id' => $this->paymentTransaction->uuid,
            'observation' => 'Payment cancelled - user request'
        ];

        $response = $this->post(route('admin.payment.cancel'), $cancelData);

        $response->assertRedirectBack();
        
        $this->assertDatabaseHas('payment_transactions', [
            'id' => $this->paymentTransaction->id,
            'status' => 'cancelled'
        ]);

        Bus::assertDispatched(BatchNotificationJob::class);
        Bus::assertDispatched(BatchBroadcastJob::class);
    }

    public function test_admin_without_manage_payment_permission_cannot_cancel_payment()
    {
        $this->actingAs($this->admin, 'admin'); // manager role without payment permissions

        $cancelData = [
            'transaction_id' => $this->paymentTransaction->uuid,
            'observation' => 'This should not work'
        ];

        $response = $this->post(route('admin.payment.cancel'), $cancelData);

        $response->assertStatus(403);
    }

    public function test_cancel_payment_requires_observation()
    {
        $this->actingAs($this->accountant, 'admin');

        $cancelData = [
            'transaction_id' => $this->paymentTransaction->uuid,
            'observation' => '' // Empty observation should fail validation
        ];

        $response = $this->post(route('admin.payment.cancel'), $cancelData);

        $response->assertSessionHasErrors(['observation'], errorBag: 'cancelPayment');
    }

    // ========================================
    // VALIDATION TESTS
    // ========================================

    public function test_approve_payment_requires_transaction_id()
    {
        $this->actingAs($this->accountant, 'admin');

        $approveData = [
            'transaction_id' => '', // Missing transaction ID
            'observation' => 'Payment approved'
        ];

        $response = $this->post(route('admin.payment.approve'), $approveData);

        $response->assertSessionHasErrors(['transaction_id'], errorBag: 'approvePayment');
    }

    public function test_reject_payment_requires_transaction_id()
    {
        $this->actingAs($this->accountant, 'admin');

        $rejectData = [
            'transaction_id' => '', // Missing transaction ID
            'observation' => 'Payment rejected'
        ];

        $response = $this->post(route('admin.payment.reject'), $rejectData);

        $response->assertSessionHasErrors(['transaction_id'], errorBag: 'rejectPayment');
    }

    public function test_cancel_payment_requires_transaction_id()
    {
        $this->actingAs($this->accountant, 'admin');

        $cancelData = [
            'transaction_id' => '', // Missing transaction ID
            'observation' => 'Payment cancelled'
        ];

        $response = $this->post(route('admin.payment.cancel'), $cancelData);

        $response->assertSessionHasErrors(['transaction_id'], errorBag: 'cancelPayment');
    }

    // ========================================
    // EDGE CASES
    // ========================================

    public function test_returns_404_for_nonexistent_transaction_uuid()
    {
        $this->actingAs($this->accountant, 'admin');

        $approveData = [
            'transaction_id' => 'nonexistent-uuid',
            'observation' => 'This should not work'
        ];

        $response = $this->post(route('admin.payment.approve'), $approveData);

        $response->assertStatus(404);
    }

    public function test_cannot_approve_already_approved_transaction()
    {
        // Create an already approved transaction
        $approvedTransaction = PaymentTransaction::factory()->create([
            'payable_id' => $this->user->id,
            'payable_type' => User::class,
            'status' => 'approved',
            'amount' => 100.00,
            'coins_credited' => 1000,
            'payment_method' => 'bank_transfer'
        ]);

        $this->actingAs($this->accountant, 'admin');

        $approveData = [
            'transaction_id' => $approvedTransaction->uuid,
            'observation' => 'Trying to approve again'
        ];

        $response = $this->post(route('admin.payment.approve'), $approveData);

        $response->assertRedirectBack();
        // The service should handle this gracefully, but the transaction status should remain approved
        $this->assertDatabaseHas('payment_transactions', [
            'id' => $approvedTransaction->id,
            'status' => 'approved'
        ]);
    }

    public function test_cannot_reject_already_rejected_transaction()
    {
        // Create an already rejected transaction
        $rejectedTransaction = PaymentTransaction::factory()->create([
            'payable_id' => $this->user->id,
            'payable_type' => User::class,
            'status' => 'rejected',
            'amount' => 100.00,
            'coins_credited' => 1000,
            'payment_method' => 'bank_transfer'
        ]);

        $this->actingAs($this->accountant, 'admin');

        $rejectData = [
            'transaction_id' => $rejectedTransaction->uuid,
            'observation' => 'Trying to reject again'
        ];

        $response = $this->post(route('admin.payment.reject'), $rejectData);

        $response->assertRedirectBack();
        // The service should handle this gracefully, but the transaction status should remain rejected
        $this->assertDatabaseHas('payment_transactions', [
            'id' => $rejectedTransaction->id,
            'status' => 'rejected'
        ]);
    }

    public function test_cannot_cancel_already_cancelled_transaction()
    {
        // Create an already cancelled transaction
        $cancelledTransaction = PaymentTransaction::factory()->create([
            'payable_id' => $this->user->id,
            'payable_type' => User::class,
            'status' => 'cancelled',
            'amount' => 100.00,
            'coins_credited' => 1000,
            'payment_method' => 'bank_transfer'
        ]);

        $this->actingAs($this->accountant, 'admin');

        $cancelData = [
            'transaction_id' => $cancelledTransaction->uuid,
            'observation' => 'Trying to cancel again'
        ];

        $response = $this->post(route('admin.payment.cancel'), $cancelData);

        $response->assertRedirectBack();
        // The service should handle this gracefully, but the transaction status should remain cancelled
        $this->assertDatabaseHas('payment_transactions', [
            'id' => $cancelledTransaction->id,
            'status' => 'cancelled'
        ]);
    }

    public function test_observation_field_has_max_length_validation()
    {
        $this->actingAs($this->accountant, 'admin');

        $longObservation = str_repeat('a', 1001); // Exceeds 1000 character limit

        $approveData = [
            'transaction_id' => $this->paymentTransaction->uuid,
            'observation' => $longObservation
        ];

        $response = $this->post(route('admin.payment.approve'), $approveData);

        $response->assertSessionHasErrors(['observation'], errorBag: 'approvePayment');
    }
} 