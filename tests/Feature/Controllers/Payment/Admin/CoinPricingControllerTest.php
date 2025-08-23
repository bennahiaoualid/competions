<?php

namespace Tests\Feature\Controllers\Payment\Admin;

use Tests\TestCase;
use App\Models\Admin\Admin;
use App\Models\Payment\CoinPricing;
use App\Enums\UserTypeEnum;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CoinPricingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $owner;
    protected Admin $accountant;
    protected Admin $admin;
    protected CoinPricing $coinPricing;

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

        // Create coin pricing for testing
        $this->coinPricing = CoinPricing::factory()->create([
            'user_type' => 'user',
            'base_amount' => 100,
            'base_coins' => 1000,
            'is_active' => true
        ]);
    }

    // ========================================
    // AUTHENTICATION TESTS
    // ========================================

    public function test_unauthenticated_users_cannot_access_coin_pricing_routes()
    {
        $routes = [
            ['get', route('admin.payment.coin_pricing.index')],
            ['post', route('admin.payment.coin_pricing.store')],
            ['delete', route('admin.payment.coin_pricing.destroy')],
            ['patch', route('admin.payment.coin_pricing.activate')],
            ['patch', route('admin.payment.coin_pricing.deactivate')],
        ];

        foreach ($routes as [$method, $uri]) {
            $response = $this->{$method}($uri);
            $response->assertRedirect(route('login'));
        }
    }

    // ========================================
    // INDEX METHOD TESTS
    // ========================================

    public function test_owner_can_view_coin_pricing()
    {
        $this->actingAs($this->owner, 'admin');

        $response = $this->get(route('admin.payment.coin_pricing.index'));

        $response->assertOk();
        $response->assertViewIs('pages.admin.payment.pricing');
        $response->assertViewHas('types', UserTypeEnum::values());
    }

    public function test_accountant_can_view_coin_pricing()
    {
        $this->actingAs($this->accountant, 'admin');

        $response = $this->get(route('admin.payment.coin_pricing.index'));

        $response->assertOk();
        $response->assertViewIs('pages.admin.payment.pricing');
        $response->assertViewHas('types', UserTypeEnum::values());
    }

    public function test_regular_admin_cannot_view_coin_pricing()
    {
        $this->actingAs($this->admin, 'admin');

        $response = $this->get(route('admin.payment.coin_pricing.index'));

        $response->assertStatus(403);
    }

    // ========================================
    // STORE METHOD TESTS
    // ========================================

    public function test_owner_cannot_create_coin_pricing()
    {
        $this->actingAs($this->owner, 'admin');

        $pricingData = [
            'name' => 'Premium User Pricing',
            'display_name' => 'Premium User Package',
            'user_type' => 'user',
            'base_amount' => 200,
            'base_coins' => 2500
        ];

        $response = $this->post(route('admin.payment.coin_pricing.store'), $pricingData);

        $response->assertStatus(403);
        
        $this->assertDatabaseMissing('coin_pricing', [
            'name' => 'Premium User Pricing'
        ]);
    }

    public function test_accountant_with_manage_coin_pricing_permission_can_create_coin_pricing()
    {
        $this->actingAs($this->accountant, 'admin');

        $pricingData = [
            'name' => 'Accountant Pricing',
            'display_name' => 'Accountant Package',
            'user_type' => 'admin',
            'base_amount' => 150,
            'base_coins' => 1800
        ];

        $response = $this->post(route('admin.payment.coin_pricing.store'), $pricingData);

        $response->assertRedirectBack();
        
        $this->assertDatabaseHas('coin_pricing', [
            'name' => 'Accountant Pricing',
            'display_name' => 'Accountant Package',
            'user_type' => 'admin',
            'base_amount' => 150.00,
            'base_coins' => 1800
        ]);
    }

    public function test_regular_admin_cannot_create_coin_pricing()
    {
        $this->actingAs($this->admin, 'admin');

        $pricingData = [
            'name' => 'Admin Pricing',
            'display_name' => 'Admin Package',
            'user_type' => 'admin',
            'base_amount' => 300,
            'base_coins' => 3500
        ];

        $response = $this->post(route('admin.payment.coin_pricing.store'), $pricingData);

        $response->assertStatus(403);
    }

    // ========================================
    // DESTROY METHOD TESTS
    // ========================================

    public function test_owner_cannot_delete_coin_pricing()
    {
        $this->actingAs($this->owner, 'admin');

        $pricingToDelete = CoinPricing::factory()->create([
            'name' => 'Pricing to Delete',
            'user_type' => 'user',
            'base_amount' => 50,
            'base_coins' => 500
        ]);

        $response = $this->delete(route('admin.payment.coin_pricing.destroy'), [
            'pricing_id' => $pricingToDelete->id
        ]);

        $response->assertStatus(403);
        
        $this->assertDatabaseHas('coin_pricing', [
            'id' => $pricingToDelete->id
        ]);
    }

    public function test_accountant_with_manage_coin_pricing_permission_can_delete_coin_pricing()
    {
        $this->actingAs($this->accountant, 'admin');

        $pricingToDelete = CoinPricing::factory()->create([
            'name' => 'Accountant Pricing to Delete',
            'user_type' => 'admin',
            'base_amount' => 75,
            'base_coins' => 800
        ]);

        $response = $this->delete(route('admin.payment.coin_pricing.destroy'), [
            'pricing_id' => $pricingToDelete->id
        ]);

        $response->assertRedirectBack();
        
        $this->assertDatabaseMissing('coin_pricing', [
            'id' => $pricingToDelete->id
        ]);
    }

    public function test_regular_admin_cannot_delete_coin_pricing()
    {
        $this->actingAs($this->admin, 'admin');

        $pricingToDelete = CoinPricing::factory()->create([
            'name' => 'Admin Pricing to Delete',
            'user_type' => 'admin',
            'base_amount' => 100,
            'base_coins' => 1200
        ]);

        $response = $this->delete(route('admin.payment.coin_pricing.destroy'), [
            'pricing_id' => $pricingToDelete->id
        ]);

        $response->assertStatus(403);
        
        // Pricing should still exist
        $this->assertDatabaseHas('coin_pricing', [
            'id' => $pricingToDelete->id
        ]);
    }

    // ========================================
    // ACTIVATE METHOD TESTS
    // ========================================

    public function test_owner_cannot_activate_coin_pricing()
    {
        $this->actingAs($this->owner, 'admin');

        $inactivePricing = CoinPricing::factory()->create([
            'name' => 'Inactive Pricing',
            'user_type' => 'user',
            'base_amount' => 80,
            'base_coins' => 900,
            'is_active' => false
        ]);

        $response = $this->patch(route('admin.payment.coin_pricing.activate'), [
            'pricing_id' => $inactivePricing->id
        ]);

        $response->assertStatus(403);
        
        $this->assertDatabaseHas('coin_pricing', [
            'id' => $inactivePricing->id,
            'is_active' => false
        ]);
    }


    public function test_regular_admin_cannot_activate_coin_pricing()
    {
        $this->actingAs($this->admin, 'admin');

        $inactivePricing = CoinPricing::factory()->create([
            'name' => 'Admin Inactive Pricing',
            'user_type' => 'admin',
            'base_amount' => 110,
            'base_coins' => 1300,
            'is_active' => false
        ]);

        $response = $this->patch(route('admin.payment.coin_pricing.activate'), [
            'pricing_id' => $inactivePricing->id
        ]);

        $response->assertStatus(403);
        
        // Pricing should remain inactive
        $this->assertDatabaseHas('coin_pricing', [
            'id' => $inactivePricing->id,
            'is_active' => false
        ]);
    }

    // ========================================
    // DEACTIVATE METHOD TESTS
    // ========================================

    public function test_owner_cannot_deactivate_coin_pricing()
    {
        $this->actingAs($this->owner, 'admin');

        $activePricing = CoinPricing::factory()->create([
            'name' => 'Active Pricing',
            'user_type' => 'user',
            'base_amount' => 120,
            'base_coins' => 1400,
            'is_active' => true
        ]);

        $response = $this->patch(route('admin.payment.coin_pricing.deactivate'), [
            'pricing_id' => $activePricing->id
        ]);

        $response->assertStatus(403);
        
        $this->assertDatabaseHas('coin_pricing', [
            'id' => $activePricing->id,
            'is_active' => true
        ]);
    }

    public function test_accountant_with_manage_coin_pricing_permission_can_deactivate_coin_pricing()
    {
        $this->actingAs($this->accountant, 'admin');

        $activePricing = CoinPricing::factory()->create([
            'name' => 'Accountant Active Pricing',
            'user_type' => 'admin',
            'base_amount' => 130,
            'base_coins' => 1500,
            'is_active' => true
        ]);

        $response = $this->patch(route('admin.payment.coin_pricing.deactivate'), [
            'pricing_id' => $activePricing->id
        ]);

        $response->assertRedirectBack();
        
        $this->assertDatabaseHas('coin_pricing', [
            'id' => $activePricing->id,
            'is_active' => false
        ]);
    }

    public function test_regular_admin_cannot_deactivate_coin_pricing()
    {
        $this->actingAs($this->admin, 'admin');

        $activePricing = CoinPricing::factory()->create([
            'name' => 'Admin Active Pricing',
            'user_type' => 'admin',
            'base_amount' => 140,
            'base_coins' => 1600,
            'is_active' => true
        ]);

        $response = $this->patch(route('admin.payment.coin_pricing.deactivate'), [
            'pricing_id' => $activePricing->id
        ]);

        $response->assertStatus(403);
        
        // Pricing should remain active
        $this->assertDatabaseHas('coin_pricing', [
            'id' => $activePricing->id,
            'is_active' => true
        ]);
    }

    // ========================================
    // VALIDATION TESTS
    // ========================================

    public function test_create_pricing_requires_name()
    {
        $this->actingAs($this->accountant, 'admin');

        $pricingData = [
            'name' => '', // Missing required field
            'display_name' => 'Test Display Name',
            'user_type' => 'user',
            'base_amount' => 100,
            'base_coins' => 1000
        ];

        $response = $this->post(route('admin.payment.coin_pricing.store'), $pricingData);

        $response->assertSessionHasErrors(['name'], errorBag: 'createCoinPricing');
    }

    public function test_create_pricing_requires_user_type()
    {
        $this->actingAs($this->accountant, 'admin');

        $pricingData = [
            'name' => 'Test Pricing',
            'display_name' => 'Test Display Name',
            'user_type' => '', // Missing required field
            'base_amount' => 100,
            'base_coins' => 1000
        ];

        $response = $this->post(route('admin.payment.coin_pricing.store'), $pricingData);

        $response->assertSessionHasErrors(['user_type'], errorBag: 'createCoinPricing');
    }

    public function test_create_pricing_requires_base_amount()
    {
        $this->actingAs($this->accountant, 'admin');

        $pricingData = [
            'name' => 'Test Pricing',
            'display_name' => 'Test Display Name',
            'user_type' => 'user',
            'base_amount' => '', // Missing required field
            'base_coins' => 1000
        ];

        $response = $this->post(route('admin.payment.coin_pricing.store'), $pricingData);

        $response->assertSessionHasErrors(['base_amount'], errorBag: 'createCoinPricing');
    }

    public function test_create_pricing_requires_base_coins()
    {
        $this->actingAs($this->accountant, 'admin');

        $pricingData = [
            'name' => 'Test Pricing',
            'display_name' => 'Test Display Name',
            'user_type' => 'user',
            'base_amount' => 100,
            'base_coins' => '' // Missing required field
        ];

        $response = $this->post(route('admin.payment.coin_pricing.store'), $pricingData);

        $response->assertSessionHasErrors(['base_coins'], errorBag: 'createCoinPricing');
    }

    public function test_user_type_must_be_valid_enum_value()
    {
        $this->actingAs($this->accountant, 'admin');

        $pricingData = [
            'name' => 'Test Pricing',
            'display_name' => 'Test Display Name',
            'user_type' => 'invalid_type', // Invalid enum value
            'base_amount' => 100,
            'base_coins' => 1000
        ];

        $response = $this->post(route('admin.payment.coin_pricing.store'), $pricingData);

        $response->assertSessionHasErrors(['user_type'], errorBag: 'createCoinPricing');
    }

    public function test_base_amount_must_be_between_10_and_10000()
    {
        $this->actingAs($this->accountant, 'admin');

        // Test amount too low
        $pricingDataLow = [
            'name' => 'Low Amount Pricing',
            'display_name' => 'Test Display Name',
            'user_type' => 'user',
            'base_amount' => 5, // Below minimum
            'base_coins' => 1000
        ];

        $response = $this->post(route('admin.payment.coin_pricing.store'), $pricingDataLow);
        $response->assertSessionHasErrors(['base_amount'], errorBag: 'createCoinPricing');

        // Test amount too high
        $pricingDataHigh = [
            'name' => 'High Amount Pricing',
            'display_name' => 'Test Display Name',
            'user_type' => 'user',
            'base_amount' => 15000, // Above maximum
            'base_coins' => 1000
        ];

        $response = $this->post(route('admin.payment.coin_pricing.store'), $pricingDataHigh);
        $response->assertSessionHasErrors(['base_amount'], errorBag: 'createCoinPricing');
    }

    public function test_base_coins_must_be_between_1_and_1000000()
    {
        $this->actingAs($this->accountant, 'admin');

        // Test coins too low
        $pricingDataLow = [
            'name' => 'Low Coins Pricing',
            'display_name' => 'Test Display Name',
            'user_type' => 'user',
            'base_amount' => 100,
            'base_coins' => 0 // Below minimum
        ];

        $response = $this->post(route('admin.payment.coin_pricing.store'), $pricingDataLow);
        $response->assertSessionHasErrors(['base_coins'], errorBag: 'createCoinPricing');

        // Test coins too high
        $pricingDataHigh = [
            'name' => 'High Coins Pricing',
            'display_name' => 'Test Display Name',
            'user_type' => 'user',
            'base_amount' => 100,
            'base_coins' => 2000000 // Above maximum
        ];

        $response = $this->post(route('admin.payment.coin_pricing.store'), $pricingDataHigh);
        $response->assertSessionHasErrors(['base_coins'], errorBag: 'createCoinPricing');
    }

    public function test_name_must_be_unique()
    {
        $this->actingAs($this->accountant, 'admin');

        // Create first pricing
        $firstPricing = CoinPricing::factory()->create([
            'name' => 'Duplicate Name',
            'user_type' => 'user',
            'base_amount' => 100,
            'base_coins' => 1000
        ]);

        // Try to create second pricing with same name
        $pricingData = [
            'name' => 'Duplicate Name', // Same name
            'display_name' => 'Different Display Name',
            'user_type' => 'admin',
            'base_amount' => 200,
            'base_coins' => 2000
        ];

        $response = $this->post(route('admin.payment.coin_pricing.store'), $pricingData);

        $response->assertSessionHasErrors(['name'], errorBag: 'createCoinPricing');
    }

    // ========================================
    // EDGE CASES
    // ========================================

    public function test_can_create_pricing_without_display_name()
    {
        $this->actingAs($this->accountant, 'admin');

        $pricingData = [
            'name' => 'No Display Name Pricing',
            'display_name' => '', // Optional field
            'user_type' => 'user',
            'base_amount' => 100,
            'base_coins' => 1000
        ];

        $response = $this->post(route('admin.payment.coin_pricing.store'), $pricingData);

        $response->assertRedirectBack();
        
        $this->assertDatabaseHas('coin_pricing', [
            'name' => 'No Display Name Pricing',
            'display_name' => null
        ]);
    }

    public function test_can_create_pricing_with_minimum_values()
    {
        $this->actingAs($this->accountant, 'admin');

        $pricingData = [
            'name' => 'Minimum Values Pricing',
            'display_name' => 'Test Display Name',
            'user_type' => 'user',
            'base_amount' => 10, // Minimum allowed
            'base_coins' => 1 // Minimum allowed
        ];

        $response = $this->post(route('admin.payment.coin_pricing.store'), $pricingData);

        $response->assertRedirectBack();
        
        $this->assertDatabaseHas('coin_pricing', [
            'name' => 'Minimum Values Pricing',
            'base_amount' => 10.00,
            'base_coins' => 1
        ]);
    }

    public function test_can_create_pricing_with_maximum_values()
    {
        $this->actingAs($this->accountant, 'admin');

        $pricingData = [
            'name' => 'Maximum Values Pricing',
            'display_name' => 'Test Display Name',
            'user_type' => 'user',
            'base_amount' => 10000, // Maximum allowed
            'base_coins' => 1000000 // Maximum allowed
        ];

        $response = $this->post(route('admin.payment.coin_pricing.store'), $pricingData);

        $response->assertRedirectBack();
        
        $this->assertDatabaseHas('coin_pricing', [
            'name' => 'Maximum Values Pricing',
            'base_amount' => 10000.00,
            'base_coins' => 1000000
        ]);
    }

    public function test_can_create_pricing_for_all_user_types()
    {
        $this->actingAs($this->accountant, 'admin');

        $userTypes = UserTypeEnum::values();

        foreach ($userTypes as $userType) {
            $pricingData = [
                'name' => "Pricing for {$userType}",
                'display_name' => "Display Name for {$userType}",
                'user_type' => $userType,
                'base_amount' => 100,
                'base_coins' => 1000
            ];

            $response = $this->post(route('admin.payment.coin_pricing.store'), $pricingData);
            $response->assertRedirectBack();
            
            $this->assertDatabaseHas('coin_pricing', [
                'name' => "Pricing for {$userType}",
                'user_type' => $userType
            ]);
        }
    }

    public function test_delete_returns_404_for_nonexistent_pricing()
    {
        $this->actingAs($this->accountant, 'admin');

        $response = $this->delete(route('admin.payment.coin_pricing.destroy'), [
            'pricing_id' => 99999
        ]);

        $response->assertStatus(404);
    }

    public function test_activate_returns_404_for_nonexistent_pricing()
    {
        $this->actingAs($this->accountant, 'admin');

        $response = $this->patch(route('admin.payment.coin_pricing.activate'), [
            'pricing_id' => 99999
        ]);

        $response->assertStatus(404);
    }

    public function test_deactivate_returns_404_for_nonexistent_pricing()
    {
        $this->actingAs($this->accountant, 'admin');

        $response = $this->patch(route('admin.payment.coin_pricing.deactivate'), [
            'pricing_id' => 99999
        ]);

        $response->assertStatus(404);
    }
} 