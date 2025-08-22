<?php

namespace Tests\Unit\Services\Payment;

use Mockery;
use Tests\TestCase;
use App\Models\Admin\Admin;
use App\Models\Payment\CoinOffer;
use App\Contracts\FlasherInterface;
use App\Models\Payment\CoinPricing;
use Illuminate\Support\Facades\Auth;
use App\Services\Payment\CoinPricingService;
use App\Contracts\TransactionManagerInterface;
use App\Enums\UserTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CoinPricingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $coinPricingService;
    protected $transactionManager;
    protected $flasher;
    protected $auth_admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->transactionManager = Mockery::mock(TransactionManagerInterface::class);
        $this->flasher = Mockery::mock(FlasherInterface::class);
        
        $this->coinPricingService = new CoinPricingService(
            $this->transactionManager,
            $this->flasher
        );

        // Mock Auth facade
        $this->auth_admin = Admin::factory()->create();
        Auth::shouldReceive('user')->andReturn($this->auth_admin);
        Auth::shouldReceive('id')->andReturn($this->auth_admin->id);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_calculate_coins_without_offer()
    {
        $coinPricing = CoinPricing::factory()->create([
            'base_coins' => 100,
            'user_type' => UserTypeEnum::BOTH->value,
            'is_active' => true
        ]);

        $result = $this->coinPricingService->calculateCoins($coinPricing);

        $this->assertEquals(100, $result);
    }

    public function test_calculate_coins_with_active_offer()
    {
        $coinPricing = CoinPricing::factory()->create([
            'base_coins' => 100,
            'user_type' => UserTypeEnum::BOTH->value,
            'is_active' => true
        ]);

        $offer = CoinOffer::factory()->create([
            'coin_pricing_id' => $coinPricing->id,
            'discount_percentage' => 50,
            'expired' => false,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay()
        ]);

        $result = $this->coinPricingService->calculateCoins($coinPricing);

        // The offer should apply and give 150 coins (100 + 50% bonus)
        $this->assertEquals(150, $result);
    }

    public function test_calculate_coins_with_expired_offer()
    {
        $coinPricing = CoinPricing::factory()->create([
            'base_coins' => 100,
            'user_type' => UserTypeEnum::BOTH->value,
            'is_active' => true
        ]);

        $offer = CoinOffer::factory()->create([
            'coin_pricing_id' => $coinPricing->id,
            'discount_percentage' => 50,
            'expired' => true,
            'start_date' => now()->subDays(10),
            'end_date' => now()->subDays(5) // Expired
        ]);

        $result = $this->coinPricingService->calculateCoins($coinPricing);

        // Should return base coins since offer is expired
        $this->assertEquals(100, $result);
    }


    public function test_create_pricing_success()
    {
        $data = [
            'name' => 'Test Pricing',
            'display_name' => 'Test Pricing',
            'user_type' => UserTypeEnum::BOTH->value,
            'base_amount' => 10.0,
            'base_coins' => 100,
            'is_active' => true,
        ];

        $this->flasher->shouldReceive('crudSuccess')
            ->once()
            ->with('saved');

        $result = $this->coinPricingService->createPricing($data);

        $this->assertTrue($result);
        $this->assertDatabaseHas('coin_pricing', [
            'user_type' => UserTypeEnum::BOTH->value,
            'base_coins' => 100,
            'base_amount' => 10.0,
            'created_by_admin_id' => $this->auth_admin->id
        ]);
    }


    public function test_delete_pricing_success()
    {
        $pricing = CoinPricing::factory()->create();

        $this->flasher->shouldReceive('crudSuccess')
            ->once()
            ->with('deleted');

        $result = $this->coinPricingService->deletePricing($pricing->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('coin_pricing', ['id' => $pricing->id]);
    }


    public function test_change_pricing_status_success()
    {
        $pricing = CoinPricing::factory()->create(['is_active' => false]);

        $this->flasher->shouldReceive('crudSuccess')
            ->once()
            ->with('updated');

        $result = $this->coinPricingService->changePricingStatus($pricing->id, true);

        $this->assertTrue($result);
        $this->assertDatabaseHas('coin_pricing', [
            'id' => $pricing->id,
            'is_active' => true
        ]);
    }


    public function test_create_offer_success()
    {
        $coinPricing = CoinPricing::factory()->create();
        $data = [
            'coin_pricing_id' => $coinPricing->id,
            'discount_percentage' => 20,
            'name' => 'Test Offer',
            'description' => 'Test Description',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay()
        ];

        $this->flasher->shouldReceive('crudSuccess')
            ->once()
            ->with('saved');

        $result = $this->coinPricingService->createOffer($data);

        $this->assertTrue($result);
        $this->assertDatabaseHas('coin_offers', $data);
    }

    public function test_delete_offer_success()
    {
        $offer = CoinOffer::factory()->create();

        $this->flasher->shouldReceive('crudSuccess')
            ->once()
            ->with('deleted');

        $result = $this->coinPricingService->deleteOffer($offer);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('coin_offers', ['id' => $offer->id]);
    }

} 