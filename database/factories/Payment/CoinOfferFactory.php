<?php

namespace Database\Factories\Payment;

use App\Models\Admin\Admin;
use App\Models\Payment\CoinOffer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CoinOfferFactory extends Factory
{
    protected $model = CoinOffer::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-1 month', '+1 month');
        $endDate = $this->faker->dateTimeBetween($startDate, '+3 months');

        return [
            'coin_pricing_id' => \App\Models\Payment\CoinPricing::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'discount_percentage' => $this->faker->numberBetween(5, 50),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'expired' => false,
            'created_by_admin_id' => Admin::factory(),
        ];
    }



    /**
     * Indicate that the offer is active (not expired)
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'expired' => false,
        ]);
    }

    /**
     * Indicate that the offer is inactive (expired)
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'expired' => true,
        ]);
    }

    /**
     * Create currently valid offer
     */
    public function currentlyValid(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => now()->subDays(rand(1, 10)),
            'end_date' => now()->addDays(rand(1, 30)),
            'is_active' => true,
        ]);
    }

    /**
     * Create expired offer
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => now()->subDays(rand(20, 60)),
            'end_date' => now()->subDays(rand(1, 19)),
            'is_active' => true,
        ]);
    }

    /**
     * Create pending offer (not started yet)
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => now()->addDays(rand(1, 30)),
            'end_date' => now()->addDays(rand(31, 60)),
            'is_active' => true,
        ]);
    }

    /**
     * Create offer with specific discount percentage
     */
    public function withDiscount(int $percentage): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_percentage' => $percentage,
        ]);
    }



    /**
     * Create weekend special offer
     */
    public function weekendSpecial(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Weekend Special',
            'description' => 'Extra coins for weekend purchases',
            'discount_percentage' => 20,
        ])->currentlyValid();
    }

    /**
     * Create new user bonus offer
     */
    public function newUserBonus(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'New User Bonus',
            'description' => 'Welcome bonus for new users',
            'discount_percentage' => 25,
        ])->currentlyValid();
    }

    /**
     * Create bulk purchase offer
     */
    public function bulkPurchase(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Bulk Purchase Bonus',
            'description' => 'Extra coins for large purchases',
            'discount_percentage' => 15,
        ])->currentlyValid();
    }
} 