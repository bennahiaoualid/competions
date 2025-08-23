<?php

namespace Tests\Feature\Controllers\Payment\Admin;

use Tests\TestCase;
use App\Models\Admin\Admin;
use App\Models\Payment\CoinOffer;
use App\Models\Payment\CoinPricing;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;

class CoinOfferControllerTest extends TestCase
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

    public function test_unauthenticated_users_cannot_access_coin_offer_routes()
    {
        $routes = [
            ['get', route('admin.payment.coin_offers.index')],
            ['post', route('admin.payment.coin_offers.store')],
            ['delete', route('admin.payment.coin_offers.destroy')],
        ];

        foreach ($routes as [$method, $uri]) {
            $response = $this->{$method}($uri);
            $response->assertRedirect(route('login'));
        }
    }

    // ========================================
    // INDEX METHOD TESTS
    // ========================================

    public function test_owner_can_view_coin_offers()
    {
        $this->actingAs($this->owner, 'admin');

        $response = $this->get(route('admin.payment.coin_offers.index'));

        $response->assertOk();
        $response->assertViewIs('pages.admin.payment.offers');
    }

    public function test_accountant_can_view_coin_offers()
    {
        $this->actingAs($this->accountant, 'admin');

        $response = $this->get(route('admin.payment.coin_offers.index'));

        $response->assertOk();
        $response->assertViewIs('pages.admin.payment.offers');
    }


    public function test_regular_admin_cannot_view_coin_offers()
    {
        $this->actingAs($this->admin, 'admin');

        $response = $this->get(route('admin.payment.coin_offers.index'));

        $response->assertStatus(403);
    }

    // ========================================
    // STORE METHOD TESTS
    // ========================================

    public function test_accountant_admin_with_manage_payment_offer_permission_can_create_coin_offer()
    {
        $this->actingAs($this->accountant, 'admin');

        $offerData = [
            'coin_pricing_id' => $this->coinPricing->id,
            'name' => 'Accountant Offer',
            'description' => 'Created by accountant',
            'discount_percentage' => 15,
            'start_date' => now()->addDay()->format('Y-m-d H:i'),
            'end_date' => now()->addDays(15)->format('Y-m-d H:i')
        ];

        $response = $this->post(route('admin.payment.coin_offers.store'), $offerData);

        $response->assertRedirectBack();
        
        $this->assertDatabaseHas('coin_offers', [
            'coin_pricing_id' => $this->coinPricing->id,
            'name' => 'Accountant Offer',
            'discount_percentage' => 15
        ]);
    }


    public function test_regular_admin_without_manage_payment_offer_permission_cannot_create_coin_offer()
    {
        $this->actingAs($this->admin, 'admin');

        $offerData = [
            'coin_pricing_id' => $this->coinPricing->id,
            'name' => 'Manager Offer',
            'description' => 'This should not work',
            'discount_percentage' => 10,
            'start_date' => now()->addDay()->format('Y-m-d H:i'),
            'end_date' => now()->addDays(10)->format('Y-m-d H:i')
        ];

        $response = $this->post(route('admin.payment.coin_offers.store'), $offerData);

        $response->assertStatus(403);
    }

    // ========================================
    // DESTROY METHOD TESTS
    // ========================================

    public function test_accountant_admin_with_manage_payment_offer_permission_can_delete_coin_offer()
    {
        $this->actingAs($this->accountant, 'admin');

        $coinOffer = CoinOffer::factory()->create([
            'coin_pricing_id' => $this->coinPricing->id,
            'name' => 'Accountant Offer to Delete',
            'discount_percentage' => 25
        ]);

        $response = $this->delete(route('admin.payment.coin_offers.destroy'), [
            'offer_id' => $coinOffer->id
        ]);

        $response->assertRedirectBack();
        
        $this->assertDatabaseMissing('coin_offers', [
            'id' => $coinOffer->id
        ]);
    }

    public function test_regular_admin_without_manage_payment_offer_permission_cannot_delete_coin_offer()
    {
        $this->actingAs($this->admin, 'admin');

        $coinOffer = CoinOffer::factory()->create([
            'coin_pricing_id' => $this->coinPricing->id,
            'name' => 'Manager Offer to Delete',
            'discount_percentage' => 20
        ]);

        $response = $this->delete(route('admin.payment.coin_offers.destroy'), [
            'offer_id' => $coinOffer->id
        ]);

        $response->assertStatus(403);
        
        // Offer should still exist
        $this->assertDatabaseHas('coin_offers', [
            'id' => $coinOffer->id
        ]);
    }

    // ========================================
    // VALIDATION TESTS
    // ========================================

    public function test_create_offer_requires_coin_pricing_id()
    {
        $this->actingAs($this->accountant, 'admin');

        $offerData = [
            'coin_pricing_id' => '', // Missing required field
            'name' => 'Test Offer',
            'description' => 'Test description',
            'discount_percentage' => 20,
            'start_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'end_date' => now()->addDays(30)->format('Y-m-d H:i:s')
        ];

        $response = $this->post(route('admin.payment.coin_offers.store'), $offerData);

        $response->assertSessionHasErrors(['coin_pricing_id'], errorBag: 'createCoinOffer');
    }

    public function test_create_offer_requires_name()
    {
        $this->actingAs($this->accountant, 'admin');

        $offerData = [
            'coin_pricing_id' => $this->coinPricing->id,
            'name' => '', // Missing required field
            'description' => 'Test description',
            'discount_percentage' => 20,
            'start_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'end_date' => now()->addDays(30)->format('Y-m-d H:i:s')
        ];

        $response = $this->post(route('admin.payment.coin_offers.store'), $offerData);

        $response->assertSessionHasErrors(['name'], errorBag: 'createCoinOffer');
    }

    public function test_create_offer_requires_discount_percentage()
    {
        $this->actingAs($this->accountant, 'admin');

        $offerData = [
            'coin_pricing_id' => $this->coinPricing->id,
            'name' => 'Test Offer',
            'description' => 'Test description',
            'discount_percentage' => '', // Missing required field
            'start_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'end_date' => now()->addDays(30)->format('Y-m-d H:i:s')
        ];

        $response = $this->post(route('admin.payment.coin_offers.store'), $offerData);

        $response->assertSessionHasErrors(['discount_percentage'], errorBag: 'createCoinOffer');
    }

    public function test_discount_percentage_must_be_between_5_and_90()
    {
        $this->actingAs($this->accountant, 'admin');

        // Test discount too low
        $offerDataLow = [
            'coin_pricing_id' => $this->coinPricing->id,
            'name' => 'Low Discount Offer',
            'description' => 'Test description',
            'discount_percentage' => 3, // Below minimum
            'start_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'end_date' => now()->addDays(30)->format('Y-m-d H:i:s')
        ];

        $response = $this->post(route('admin.payment.coin_offers.store'), $offerDataLow);
        $response->assertSessionHasErrors(['discount_percentage'], errorBag: 'createCoinOffer');

        // Test discount too high
        $offerDataHigh = [
            'coin_pricing_id' => $this->coinPricing->id,
            'name' => 'High Discount Offer',
            'description' => 'Test description',
            'discount_percentage' => 95, // Above maximum
            'start_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'end_date' => now()->addDays(30)->format('Y-m-d H:i:s')
        ];

        $response = $this->post(route('admin.payment.coin_offers.store'), $offerDataHigh);
        $response->assertSessionHasErrors(['discount_percentage'], errorBag: 'createCoinOffer');
    }

    public function test_start_date_must_be_in_future()
    {
        $this->actingAs($this->accountant, 'admin');

        $offerData = [
            'coin_pricing_id' => $this->coinPricing->id,
            'name' => 'Past Start Date Offer',
            'description' => 'Test description',
            'discount_percentage' => 20,
            'start_date' => now()->subDay()->format('Y-m-d H:i:s'), // Past date
            'end_date' => now()->addDays(30)->format('Y-m-d H:i:s')
        ];

        $response = $this->post(route('admin.payment.coin_offers.store'), $offerData);

        $response->assertSessionHasErrors(['start_date'], errorBag: 'createCoinOffer');
    }

    public function test_end_date_must_be_after_start_date()
    {
        $this->actingAs($this->accountant, 'admin');

        $offerData = [
            'coin_pricing_id' => $this->coinPricing->id,
            'name' => 'Invalid Date Range Offer',
            'description' => 'Test description',
            'discount_percentage' => 20,
            'start_date' => now()->addDays(30)->format('Y-m-d H:i:s'),
            'end_date' => now()->addDay()->format('Y-m-d H:i:s') // Before start date
        ];

        $response = $this->post(route('admin.payment.coin_offers.store'), $offerData);

        $response->assertSessionHasErrors(['end_date'], errorBag: 'createCoinOffer');
    }

    // ========================================
    // EDGE CASES
    // ========================================

    public function test_delete_returns_404_for_nonexistent_offer()
    {
        $this->actingAs($this->accountant, 'admin');

        $response = $this->delete(route('admin.payment.coin_offers.destroy'), [
            'offer_id' => 99999
        ]);

        $response->assertStatus(404);
    }

    public function test_can_create_offer_without_description()
    {
        $this->actingAs($this->accountant, 'admin');

        $offerData = [
            'coin_pricing_id' => $this->coinPricing->id,
            'name' => 'No Description Offer',
            'description' => '', // Optional field
            'discount_percentage' => 20,
            'start_date' => now()->addDay()->format('Y-m-d H:i'),
            'end_date' => now()->addDays(30)->format('Y-m-d H:i')
        ];

        $response = $this->post(route('admin.payment.coin_offers.store'), $offerData);

        $response->assertRedirectBack();
        $this->assertDatabaseHas('coin_offers', [
            'coin_pricing_id' => $this->coinPricing->id,
            'name' => 'No Description Offer',
            'description' => null
        ]);
    }

    public function test_can_create_offer_with_minimum_discount()
    {
        $this->actingAs($this->accountant, 'admin');

        $offerData = [
            'coin_pricing_id' => $this->coinPricing->id,
            'name' => 'Minimum Discount Offer',
            'description' => 'Test description',
            'discount_percentage' => 5, // Minimum allowed
            'start_date' => now()->addDay()->format('Y-m-d H:i'),
            'end_date' => now()->addDays(30)->format('Y-m-d H:i')
        ];

        $response = $this->post(route('admin.payment.coin_offers.store'), $offerData);

        $response->assertRedirectBack();
        
        $this->assertDatabaseHas('coin_offers', [
            'name' => 'Minimum Discount Offer',
            'discount_percentage' => 5
        ]);
    }

    public function test_can_create_offer_with_maximum_discount()
    {
        $this->actingAs($this->accountant, 'admin');

        $offerData = [
            'coin_pricing_id' => $this->coinPricing->id,
            'name' => 'Maximum Discount Offer',
            'description' => 'Test description',
            'discount_percentage' => 90, // Maximum allowed
            'start_date' => now()->addDay()->format('Y-m-d H:i'),
            'end_date' => now()->addDays(30)->format('Y-m-d H:i')
        ];

        $response = $this->post(route('admin.payment.coin_offers.store'), $offerData);

        $response->assertRedirectBack();
        
        $this->assertDatabaseHas('coin_offers', [
            'name' => 'Maximum Discount Offer',
            'discount_percentage' => 90
        ]);
    }
} 