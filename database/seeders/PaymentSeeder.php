<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Admin\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Models\Payment\PaymentTransaction;
use App\Models\Payment\CoinPricing;
use App\Models\Payment\CoinOffer;

class PaymentSeeder extends Seeder
{
    /**
     * Track created records for cleanup
     */
    private array $createdRecords = [
        'admins' => [],
        'users' => [],
        'payment_transactions' => [],
        'proof_images' => [],
        'coin_pricing' => [],
        'coin_offers' => [],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting Payment System Seeder...');

        // Clean up any existing payment data first
        $this->cleanup();

        // Create test data
        $this->createAccountantAdmin();
        $this->createTestUsers();
        $this->createTestAdmins();
        $this->createCoinPricing();
        $this->createCoinOffers();
        $this->createPaymentTransactions();

        $this->command->info('✅ Payment System Seeder completed successfully!');
        $this->displaySummary();
    }

    /**
     * Create accountant admin
     */
    private function createAccountantAdmin(): void
    {
        $this->command->info('👤 Creating Accountant Admin...');

        $accountant = Admin::create([
            'name' => 'Ahmed Accountant',
            'email' => 'accountant@test.com',
            'password' => Hash::make('12345678'),
            'birthdate' => '1990-05-15',
            'gender' => 'male',
            'admin_id' => 1, // Assuming owner exists
        ]);

        // Assign accountant role
        $accountantRole = Role::where('name', 'accountant')->first();
        if ($accountantRole) {
            $accountant->assignRole($accountantRole);
        }

        $this->createdRecords['admins'][] = $accountant->id;
        $this->command->info("✅ Accountant created: {$accountant->email}");
    }

    /**
     * Create test users
     */
    private function createTestUsers(): void
    {
        $this->command->info('👥 Creating Test Users...');

        $users = [
            [
                'name' => 'John User',
                'email' => 'john.user@test.com',
                'password' => Hash::make('12345678'),
                'birthdate' => '1995-03-20',
                'gender' => 'male',
            ],
            [
                'name' => 'Sarah User',
                'email' => 'sarah.user@test.com',
                'password' => Hash::make('12345678'),
                'birthdate' => '1992-08-10',
                'gender' => 'female',
            ],
            [
                'name' => 'Mike User',
                'email' => 'mike.user@test.com',
                'password' => Hash::make('12345678'),
                'birthdate' => '1988-12-05',
                'gender' => 'male',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::create($userData);
            $this->createdRecords['users'][] = $user->id;
            $this->command->info("✅ User created: {$user->email}");
        }
    }

    /**
     * Create test admins (non-accountant)
     */
    private function createTestAdmins(): void
    {
        $this->command->info('👨‍💼 Creating Test Admins...');

        $admins = [
            [
                'name' => 'Manager Admin',
                'email' => 'manager@test.com',
                'password' => bcrypt('password123'),
                'birthdate' => '1985-07-12',
                'gender' => 'male',
            ],
            [
                'name' => 'Super Admin',
                'email' => 'super@test.com',
                'password' => bcrypt('password123'),
                'birthdate' => '1980-11-25',
                'gender' => 'male',
            ],
        ];

        foreach ($admins as $adminData) {
            $admin = Admin::create(array_merge($adminData, ['admin_id' => 1]));
            $this->createdRecords['admins'][] = $admin->id;
            $this->command->info("✅ Admin created: {$admin->email}");
        }
    }

    /**
     * Create payment transactions
     */
    private function createPaymentTransactions(): void
    {
        $this->command->info('💰 Creating Payment Transactions...');

        $users = User::whereIn('id', $this->createdRecords['users'])->get();
        $admins = Admin::whereIn('id', $this->createdRecords['admins'])->get();
        $accountant = Admin::where('email', 'accountant@test.com')->first();

        // Create user payments
        foreach ($users as $user) {
            // Pending payment
            $pendingPayment = PaymentTransaction::create([
                'payable_id' => $user->id,
                'payable_type' => User::class,
                'amount' => rand(100, 2000),
                'coins_credited' => rand(10, 200),
                'payment_method' => 'cash',
                'proof_image_path' => 'payment_proofs/test_user_pending_' . $user->id . '.jpg',
                'status' => 'pending',
            ]);
            $this->createdRecords['payment_transactions'][] = $pendingPayment->id;

            // Approved payment
            $approvedPayment = PaymentTransaction::create([
                'payable_id' => $user->id,
                'payable_type' => User::class,
                'approver_admin_id' => $accountant->id,
                'approved_at' => now()->subDays(rand(1, 30)),
                'amount' => rand(500, 3000),
                'coins_credited' => rand(50, 300),
                'payment_method' => 'bank_transfer',
                'proof_image_path' => 'payment_proofs/test_user_approved_' . $user->id . '.jpg',
                'status' => 'approved',
                'accountant_observation' => 'Payment verified successfully',
            ]);
            $this->createdRecords['payment_transactions'][] = $approvedPayment->id;

            // Rejected payment
            $rejectedPayment = PaymentTransaction::create([
                'payable_id' => $user->id,
                'payable_type' => User::class,
                'amount' => rand(100, 1000),
                'coins_credited' => rand(10, 100),
                'payment_method' => 'mobile_money',
                'proof_image_path' => 'payment_proofs/test_user_rejected_' . $user->id . '.jpg',
                'status' => 'rejected',
                'accountant_observation' => 'Invalid proof of payment',
            ]);
            $this->createdRecords['payment_transactions'][] = $rejectedPayment->id;
        }

        // Create admin payments
        foreach ($admins as $admin) {
            $adminPayment = PaymentTransaction::create([
                'payable_id' => $admin->id,
                'payable_type' => Admin::class,
                'approver_admin_id' => $accountant->id,
                'approved_at' => now()->subDays(rand(1, 15)),
                'amount' => rand(1000, 5000),
                'coins_credited' => rand(100, 500),
                'payment_method' => 'bank_transfer',
                'proof_image_path' => 'payment_proofs/test_admin_' . $admin->id . '.jpg',
                'status' => 'approved',
                'accountant_observation' => 'Admin payment approved',
            ]);
            $this->createdRecords['payment_transactions'][] = $adminPayment->id;
        }

        $this->command->info('✅ Payment transactions created successfully');
    }

    /**
     * Create coin pricing data
     */
    private function createCoinPricing(): void
    {
        $this->command->info('💰 Creating Coin Pricing...');

        $accountant = Admin::where('email', 'accountant@test.com')->first();

        // Create standard user pricing (100 DZD = 50 coins)
        $userPricing = CoinPricing::create([
            'user_type' => 'user',
            'base_amount' => 100.00,
            'base_coins' => 50,
            'is_active' => true,
            'created_by_admin_id' => $accountant->id,
        ]);
        $this->createdRecords['coin_pricing'][] = $userPricing->id;

        // Create standard admin pricing (100 DZD = 100 coins)
        $adminPricing = CoinPricing::create([
            'user_type' => 'admin',
            'base_amount' => 100.00,
            'base_coins' => 100,
            'is_active' => true,
            'created_by_admin_id' => $accountant->id,
        ]);
        $this->createdRecords['coin_pricing'][] = $adminPricing->id;

        // Create some historical pricing
        $historicalPricing = CoinPricing::create([
            'user_type' => 'user',
            'base_amount' => 100.00,
            'base_coins' => 40,
            'is_active' => false,
            'created_by_admin_id' => $accountant->id,
        ]);
        $this->createdRecords['coin_pricing'][] = $historicalPricing->id;

        $this->command->info('✅ Coin pricing created successfully');
    }

    /**
     * Create coin offers data
     */
    private function createCoinOffers(): void
    {
        $this->command->info('🎁 Creating Coin Offers...');

        $accountant = Admin::where('email', 'accountant@test.com')->first();

        // Create weekend special offer
        $weekendOffer = CoinOffer::create([
            'name' => 'Weekend Special',
            'description' => 'Extra coins for weekend purchases',
            'user_type' => 'both',
            'discount_percentage' => 20,
            'min_amount' => 100.00,
            'max_amount' => 1000.00,
            'start_date' => now()->subDays(5),
            'end_date' => now()->addDays(25),
            'is_active' => true,
            'created_by_admin_id' => $accountant->id,
        ]);
        $this->createdRecords['coin_offers'][] = $weekendOffer->id;

        // Create new user bonus
        $newUserOffer = CoinOffer::create([
            'name' => 'New User Bonus',
            'description' => 'Welcome bonus for new users',
            'user_type' => 'user',
            'discount_percentage' => 25,
            'min_amount' => 50.00,
            'max_amount' => 500.00,
            'start_date' => now()->subDays(10),
            'end_date' => now()->addDays(20),
            'is_active' => true,
            'created_by_admin_id' => $accountant->id,
        ]);
        $this->createdRecords['coin_offers'][] = $newUserOffer->id;

        // Create bulk purchase offer
        $bulkOffer = CoinOffer::create([
            'name' => 'Bulk Purchase Bonus',
            'description' => 'Extra coins for large purchases',
            'user_type' => 'both',
            'discount_percentage' => 15,
            'min_amount' => 500.00,
            'max_amount' => null,
            'start_date' => now()->subDays(15),
            'end_date' => now()->addDays(15),
            'is_active' => true,
            'created_by_admin_id' => $accountant->id,
        ]);
        $this->createdRecords['coin_offers'][] = $bulkOffer->id;

        // Create expired offer
        $expiredOffer = CoinOffer::create([
            'name' => 'Expired Special',
            'description' => 'This offer has expired',
            'user_type' => 'admin',
            'discount_percentage' => 10,
            'min_amount' => null,
            'max_amount' => null,
            'start_date' => now()->subDays(30),
            'end_date' => now()->subDays(5),
            'is_active' => true,
            'created_by_admin_id' => $accountant->id,
        ]);
        $this->createdRecords['coin_offers'][] = $expiredOffer->id;

        $this->command->info('✅ Coin offers created successfully');
    }

    /**
     * Clean up all created records
     */
    public function cleanup(): void
    {
        $this->command->info('🧹 Cleaning up existing payment data...');

        // Delete in correct order to respect foreign key constraints
        // First delete dependent tables if they exist
        if (class_exists('App\Models\Payment\PaymentAuditLog')) {
            \App\Models\Payment\PaymentAuditLog::query()->delete();
        }
        
        // Delete coin offers and pricing
        if (class_exists('App\Models\Payment\CoinOffer')) {
            CoinOffer::query()->delete();
        }
        if (class_exists('App\Models\Payment\CoinPricing')) {
            CoinPricing::query()->delete();
        }
        
        // Then delete main table
        PaymentTransaction::query()->delete();

        // Delete test admins (except owner)
        Admin::where('email', 'like', '%@test.com')->delete();

        // Delete test users
        User::where('email', 'like', '%@test.com')->delete();

        // Clean up proof images (skip if disk not configured)
        try {
            if (Storage::disk('payment_proofs')->exists('payment_proofs')) {
                Storage::disk('payment_proofs')->deleteDirectory('payment_proofs');
            }
        } catch (\Exception $e) {
            // Disk not configured, skip cleanup
        }

        $this->command->info('✅ Cleanup completed');
    }

    /**
     * Display summary of created data
     */
    private function displaySummary(): void
    {
        $this->command->info('📊 Payment System Test Data Summary:');
        $this->command->info('👤 Accountant Admin: accountant@test.com (password: 12345678)');
        $this->command->info('👥 Test Users: john.user@test.com, sarah.user@test.com, mike.user@test.com (password: 12345678)');
        $this->command->info('👨‍💼 Test Admins: manager@test.com, super@test.com (password: password123)');
        $this->command->info('💰 Payment Transactions: ' . count($this->createdRecords['payment_transactions']));
    }
} 