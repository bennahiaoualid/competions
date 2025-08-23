<?php

namespace Tests\Feature\Controllers\Payment\User;

use Tests\TestCase;
use App\Models\User;
use App\Models\Admin\Admin;
use App\Models\Payment\PaymentTransaction;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class PaymentProofControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Admin $admin;
    protected Admin $accountant;
    protected PaymentTransaction $paymentTransaction;
    protected string $proofImagePath;

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

        // Create payment transaction with proof image
        $this->paymentTransaction = PaymentTransaction::factory()->create([
            'payable_id' => $this->user->id,
            'payable_type' => User::class,
            'status' => 'pending',
            'amount' => 100.00,
            'coins_credited' => 1000,
            'payment_method' => 'bank_transfer',
            'proof_image_path' => 'payment_proofs/test_proof.jpg'
        ]);

        // Fake storage for file testing
        Storage::fake('local');
        
        // Create a fake proof image file
        $this->proofImagePath = 'payment_proofs/test_proof.jpg';
        Storage::disk('local')->put($this->proofImagePath, 'fake image content');
    }

    // ========================================
    // AUTHENTICATION TESTS
    // ========================================

    public function test_unauthenticated_users_cannot_access_payment_proof()
    {
        $response = $this->get(route('transactions.proof', ['transaction' => $this->paymentTransaction->id]));
        $response->assertRedirect(route('login'));
    }

    // ========================================
    // SHOW METHOD TESTS
    // ========================================

    public function test_user_can_view_own_payment_proof()
    {
        $this->actingAs($this->user, 'web');

        $response = $this->get(route('transactions.proof', ['transaction' => $this->paymentTransaction->id]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/jpeg');
    }

    public function test_accountant_can_view_payment_proof_with_manage_payment_permission()
    {
        $this->actingAs($this->accountant, 'admin');

        $response = $this->get(route('transactions.proof', ['transaction' => $this->paymentTransaction->id]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/jpeg');
    }

    public function test_admin_can_view_payment_proof_with_manage_payment_permission()
    {
        $this->actingAs($this->admin, 'admin');

        $response = $this->get(route('transactions.proof', ['transaction' => $this->paymentTransaction->id]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/jpeg');
    }

    // ========================================
    // AUTHORIZATION TESTS
    // ========================================

    public function test_user_cannot_view_other_user_payment_proof()
    {
        $otherUser = User::factory()->create();
        $otherTransaction = PaymentTransaction::factory()->create([
            'payable_id' => $otherUser->id,
            'payable_type' => User::class,
            'status' => 'pending',
            'amount' => 100.00,
            'coins_credited' => 1000,
            'payment_method' => 'bank_transfer',
            'proof_image_path' => 'payment_proofs/other_proof.jpg'
        ]);

        // Create the other user's proof image
        Storage::disk('local')->put('payment_proofs/other_proof.jpg', 'other user image content');

        $this->actingAs($this->user, 'web');

        $response = $this->get(route('transactions.proof', ['transaction' => $otherTransaction->id]));

        $response->assertStatus(403);
        $response->assertSee('Unauthorized');
    }

    public function test_user_without_manage_payment_permission_cannot_view_other_user_proof()
    {
        $regularUser = User::factory()->create();
        $this->actingAs($regularUser, 'web');

        $response = $this->get(route('transactions.proof', ['transaction' => $this->paymentTransaction->id]));

        $response->assertStatus(403);
        $response->assertSee('Unauthorized');
    }

    // ========================================
    // FILE EXISTENCE TESTS
    // ========================================

    public function test_returns_404_for_nonexistent_proof_image()
    {
        $transactionWithoutProof = PaymentTransaction::factory()->create([
            'payable_id' => $this->user->id,
            'payable_type' => User::class,
            'status' => 'pending',
            'amount' => 100.00,
            'coins_credited' => 1000,
            'payment_method' => 'bank_transfer',
            'proof_image_path' => 'payment_proofs/nonexistent.jpg'
        ]);

        $this->actingAs($this->user, 'web');

        $response = $this->get(route('transactions.proof', ['transaction' => $transactionWithoutProof->id]));

        $response->assertStatus(404);
    }

    public function test_returns_404_for_null_proof_image_path()
    {
        $transactionWithNullProof = PaymentTransaction::factory()->create([
            'payable_id' => $this->user->id,
            'payable_type' => User::class,
            'status' => 'pending',
            'amount' => 100.00,
            'coins_credited' => 1000,
            'payment_method' => 'bank_transfer',
            'proof_image_path' => null
        ]);

        $this->actingAs($this->user, 'web');

        $response = $this->get(route('transactions.proof', ['transaction' => $transactionWithNullProof->id]));

        $response->assertStatus(404);
    }

    public function test_returns_404_for_empty_proof_image_path()
    {
        $transactionWithEmptyProof = PaymentTransaction::factory()->create([
            'payable_id' => $this->user->id,
            'payable_type' => User::class,
            'status' => 'pending',
            'amount' => 100.00,
            'coins_credited' => 1000,
            'payment_method' => 'bank_transfer',
            'proof_image_path' => ''
        ]);

        $this->actingAs($this->user, 'web');

        $response = $this->get(route('transactions.proof', ['transaction' => $transactionWithEmptyProof->id]));

        $response->assertStatus(404);
    }

    // ========================================
    // EDGE CASES
    // ========================================

    public function test_returns_404_for_nonexistent_transaction()
    {
        $this->actingAs($this->user, 'web');

        $response = $this->get(route('transactions.proof', ['transaction' => 99999]));

        $response->assertStatus(404);
    }

    public function test_handles_transaction_with_special_characters_in_proof_path()
    {
        $transactionWithSpecialPath = PaymentTransaction::factory()->create([
            'payable_id' => $this->user->id,
            'payable_type' => User::class,
            'status' => 'pending',
            'amount' => 100.00,
            'coins_credited' => 1000,
            'payment_method' => 'bank_transfer',
            'proof_image_path' => 'payment_proofs/test_proof_with_spaces and (special) chars.jpg'
        ]);

        // Create the file with special characters
        Storage::disk('local')->put($transactionWithSpecialPath->proof_image_path, 'special chars image content');

        $this->actingAs($this->user, 'web');

        $response = $this->get(route('transactions.proof', ['transaction' => $transactionWithSpecialPath->id]));

        $response->assertOk();
    }

    public function test_handles_transaction_with_nested_directory_proof_path()
    {
        $transactionWithNestedPath = PaymentTransaction::factory()->create([
            'payable_id' => $this->user->id,
            'payable_type' => User::class,
            'status' => 'pending',
            'amount' => 100.00,
            'coins_credited' => 1000,
            'payment_method' => 'bank_transfer',
            'proof_image_path' => 'payment_proofs/2024/01/test_proof.jpg'
        ]);

        // Create the nested directory structure and file
        Storage::disk('local')->put($transactionWithNestedPath->proof_image_path, 'nested path image content');

        $this->actingAs($this->user, 'web');

        $response = $this->get(route('transactions.proof', ['transaction' => $transactionWithNestedPath->id]));

        $response->assertOk();
    }

    // ========================================
    // RESPONSE FORMAT TESTS
    // ========================================

    public function test_response_returns_file_with_correct_headers()
    {
        $this->actingAs($this->user, 'web');

        $response = $this->get(route('transactions.proof', ['transaction' => $this->paymentTransaction->id]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/jpeg');
        $response->assertHeader('Content-Disposition');
    }

    public function test_response_contains_file_content()
    {
        $this->actingAs($this->user, 'web');

        $response = $this->get(route('transactions.proof', ['transaction' => $this->paymentTransaction->id]));

        $response->assertOk();
        $response->assertSee('fake image content');
    }

    // ========================================
    // PERMISSION EDGE CASES
    // ========================================

    public function test_user_with_partial_permission_cannot_view_other_user_proof()
    {
        // Create a user with some permissions but not 'manage payment'
        $userWithPartialPermissions = User::factory()->create();
        // Note: This user has no special permissions assigned

        $this->actingAs($userWithPartialPermissions, 'web');

        $response = $this->get(route('transactions.proof', ['transaction' => $this->paymentTransaction->id]));

        $response->assertStatus(403);
    }

    public function test_admin_without_manage_payment_permission_cannot_view_proof()
    {
        $adminWithoutPaymentPermission = Admin::factory()->create();
        $adminWithoutPaymentPermission->assignRole('manager'); // Role without payment permissions

        $this->actingAs($adminWithoutPaymentPermission, 'admin');

        $response = $this->get(route('transactions.proof', ['transaction' => $this->paymentTransaction->id]));

        $response->assertStatus(403);
    }
} 