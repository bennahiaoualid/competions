<?php

namespace Database\Factories\Payment;

use App\Models\Admin\Admin;
use App\Models\Payment\CoinPricing;
use Illuminate\Database\Eloquent\Factories\Factory;

class CoinPricingFactory extends Factory
{
    protected $model = CoinPricing::class;

    public function definition(): array
    {
        return [
            'user_type' => $this->faker->randomElement(['user', 'admin']),
            'base_amount' => $this->faker->randomFloat(2, 50, 1000),
            'base_coins' => $this->faker->numberBetween(25, 500),
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
            'created_by_admin_id' => Admin::factory(),
        ];
    }

    /**
     * Indicate that the pricing is for users
     */
    public function forUsers(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => 'user',
        ]);
    }

    /**
     * Indicate that the pricing is for admins
     */
    public function forAdmins(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => 'admin',
        ]);
    }

    /**
     * Indicate that the pricing is active
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the pricing is inactive
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create pricing with specific rate (amount per coin)
     */
    public function withRate(float $amount, int $coins): static
    {
        return $this->state(fn (array $attributes) => [
            'base_amount' => $amount,
            'base_coins' => $coins,
        ]);
    }

    /**
     * Create standard user pricing (100 DZD = 50 coins)
     */
    public function standardUser(): static
    {
        return $this->forUsers()->withRate(100.00, 50)->active();
    }

    /**
     * Create standard admin pricing (100 DZD = 100 coins)
     */
    public function standardAdmin(): static
    {
        return $this->forAdmins()->withRate(100.00, 100)->active();
    }
} 