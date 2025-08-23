<?php

namespace Tests\Feature\Controllers\Payment\User;

use Tests\TestCase;
use App\Models\User;
use App\Models\Admin\Admin;
use App\Models\Payment\CoinTransaction;
use App\Enums\CoinTransactionTypeEnum;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;

class CoinTransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Admin $admin;
    protected Admin $accountant;

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

        // Create some coin transactions for the user
        CoinTransaction::factory()->purchased()->forEntity($this->user)->create([
            'amount' => 1000,
            'processed_at' => now()->subDays(5)
        ]);

        CoinTransaction::factory()->competitionGift()->forEntity($this->user)->create([
            'amount' => 500,
            'processed_at' => now()->subDays(3)
        ]);

        CoinTransaction::factory()->questionGenerate()->forEntity($this->user)->create([
            'amount' => 200,
            'processed_at' => now()->subDays(1)
        ]);

        CoinTransaction::factory()->premiumQuestionPurchase()->forEntity($this->user)->create([
            'amount' => 300,
            'processed_at' => now()
        ]);
    }

    // ========================================
    // AUTHENTICATION TESTS
    // ========================================

    public function test_unauthenticated_users_cannot_access_coin_transaction_history()
    {
        $response = $this->get(route('payment.transactions.history'));
        $response->assertRedirect(route('login'));
    }

    // ========================================
    // INDEX METHOD TESTS
    // ========================================

    public function test_authenticated_user_can_view_coin_transaction_history()
    {
        $this->actingAs($this->user, 'web');

        $response = $this->get(route('payment.transactions.history'));

        $response->assertOk();
        $response->assertViewIs('pages.payment.transactions.history');
        $response->assertViewHas('transactions');
        $response->assertViewHas('summary');
        $response->assertViewHas('filters');
        $response->assertViewHas('detailTypes');
    }

    public function test_user_can_filter_transactions_by_type()
    {
        $this->actingAs($this->user, 'web');

        $response = $this->get(route('payment.transactions.history', ['type' => 'earn']));

        $response->assertOk();
        $response->assertViewIs('pages.payment.transactions.history');
        $response->assertViewHas('filters', function ($filters) {
            return $filters['type'] === 'earn';
        });
    }

    public function test_user_can_filter_transactions_by_detail()
    {
        $this->actingAs($this->user, 'web');

        $response = $this->get(route('payment.transactions.history', ['detail' => 'purchased']));

        $response->assertOk();
        $response->assertViewIs('pages.payment.transactions.history');
        $response->assertViewHas('filters', function ($filters) {
            return $filters['detail'] === 'purchased';
        });
    }

    public function test_user_can_filter_transactions_by_date_range()
    {
        $this->actingAs($this->user, 'web');

        $dateFrom = now()->subDays(7)->format('Y-m-d');
        $dateTo = now()->subDays(1)->format('Y-m-d');

        $response = $this->get(route('payment.transactions.history', [
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ]));

        $response->assertOk();
        $response->assertViewIs('pages.payment.transactions.history');
        $response->assertViewHas('filters', function ($filters) use ($dateFrom, $dateTo) {
            return $filters['date_from'] === $dateFrom && $filters['date_to'] === $dateTo;
        });
    }

    public function test_user_can_filter_transactions_by_multiple_criteria()
    {
        $this->actingAs($this->user, 'web');

        $response = $this->get(route('payment.transactions.history', [
            'type' => 'earn',
            'detail' => 'purchased',
            'date_from' => now()->subDays(10)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d')
        ]));

        $response->assertOk();
        $response->assertViewIs('pages.payment.transactions.history');
        $response->assertViewHas('filters');
    }

    public function test_user_can_access_paginated_transactions()
    {
        $this->actingAs($this->user, 'web');

        // Create more transactions to test pagination
        CoinTransaction::factory()->count(15)->purchased()->forEntity($this->user)->create();

        $response = $this->get(route('payment.transactions.history', ['page' => 2]));

        $response->assertOk();
        $response->assertViewIs('pages.payment.transactions.history');
        $response->assertViewHas('transactions');
    }

    // ========================================
    // VIEW DATA TESTS
    // ========================================

    public function test_view_contains_correct_detail_types()
    {
        $this->actingAs($this->user, 'web');

        $response = $this->get(route('payment.transactions.history'));

        $response->assertOk();
        $response->assertViewHas('detailTypes', CoinTransactionTypeEnum::values());
    }

    public function test_view_contains_transaction_summary()
    {
        $this->actingAs($this->user, 'web');

        $response = $this->get(route('payment.transactions.history'));

        $response->assertOk();
        $response->assertViewHas('summary');
        
        $summary = $response->viewData('summary');
        $this->assertIsArray($summary);
    }

    public function test_view_contains_filtered_transactions()
    {
        $this->actingAs($this->user, 'web');

        $response = $this->get(route('payment.transactions.history', ['type' => 'earn']));

        $response->assertOk();
        $response->assertViewHas('transactions');
        
        $transactions = $response->viewData('transactions');
        $this->assertIsObject($transactions);
    }

    // ========================================
    // EDGE CASES
    // ========================================

    public function test_user_can_access_history_with_no_transactions()
    {
        $userWithoutTransactions = User::factory()->create();
        $this->actingAs($userWithoutTransactions, 'web');

        $response = $this->get(route('payment.transactions.history'));

        $response->assertOk();
        $response->assertViewIs('pages.payment.transactions.history');
        $response->assertViewHas('transactions');
        $response->assertViewHas('summary');
    }

    public function test_user_can_access_history_with_invalid_filters()
    {
        $this->actingAs($this->user, 'web');

        $response = $this->get(route('payment.transactions.history', [
            'type' => 'invalid_type',
            'detail' => 'invalid_detail',
            'date_from' => 'invalid_date',
            'date_to' => 'invalid_date'
        ]));

        $response->assertOk();
        $response->assertViewIs('pages.payment.transactions.history');
        // Should not crash with invalid filters
    }

    public function test_user_can_access_history_with_empty_filters()
    {
        $this->actingAs($this->user, 'web');

        $response = $this->get(route('payment.transactions.history', [
            'type' => '',
            'detail' => '',
            'date_from' => '',
            'date_to' => ''
        ]));

        $response->assertOk();
        $response->assertViewIs('pages.payment.transactions.history');
        // Should handle empty filter values gracefully
    }

    // ========================================
    // AUTHORIZATION TESTS
    // ========================================

    public function test_admin_can_access_coin_transaction_history_with_admin_guard()
    {
        $this->actingAs($this->admin, 'admin');

        $response = $this->get(route('payment.transactions.history'));
        $response->assertOk();
    }

    public function test_accountant_cannot_access_user_coin_transaction_history()
    {
        $this->actingAs($this->accountant, 'admin');

        $response = $this->get(route('payment.transactions.history'));
        $response->assertStatus(403);
    }

    // ========================================
    // FILTER VALIDATION TESTS
    // ========================================

    public function test_date_filters_accept_valid_date_formats()
    {
        $this->actingAs($this->user, 'web');

        $validDate = now()->subDays(5)->format('Y-m-d');

        $response = $this->get(route('payment.transactions.history', [
            'date_from' => $validDate,
            'date_to' => $validDate
        ]));

        $response->assertOk();
        $response->assertViewHas('filters', function ($filters) use ($validDate) {
            return $filters['date_from'] === $validDate && $filters['date_to'] === $validDate;
        });
    }

    public function test_type_filter_accepts_valid_types()
    {
        $this->actingAs($this->user, 'web');

        $validTypes = ['earn', 'spend'];

        foreach ($validTypes as $type) {
            $response = $this->get(route('payment.transactions.history', ['type' => $type]));
            $response->assertOk();
            $response->assertViewHas('filters', function ($filters) use ($type) {
                return $filters['type'] === $type;
            });
        }
    }

    public function test_detail_filter_accepts_valid_detail_types()
    {
        $this->actingAs($this->user, 'web');

        $validDetails = CoinTransactionTypeEnum::values();

        foreach ($validDetails as $detail) {
            $response = $this->get(route('payment.transactions.history', ['detail' => $detail]));
            $response->assertOk();
            $response->assertViewHas('filters', function ($filters) use ($detail) {
                return $filters['detail'] === $detail;
            });
        }
    }
} 