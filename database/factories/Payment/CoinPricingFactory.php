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
        $userType = $this->faker->randomElement(['user', 'admin', 'both']);
        $name = $this->generateName($userType);
        
        return [
            'name' => $name,
            'display_name' => $this->faker->optional(0.7)->sentence(3, 6),
            'user_type' => $userType,
            'base_amount' => $this->faker->randomFloat(2, 50, 1000),
            'base_coins' => $this->faker->numberBetween(25, 500),
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
            'created_by_admin_id' => Admin::factory(),
        ];
    }

    /**
     * Generate appropriate name based on user type
     */
    private function generateName(string $userType): string
    {
        $prefixes = [
            'user' => ['Standard', 'Basic', 'Premium', 'Economy', 'Starter'],
            'admin' => ['Admin', 'Professional', 'Enterprise', 'Business', 'Advanced'],
            'both' => ['Universal', 'General', 'Common', 'Shared', 'Universal']
        ];

        $suffixes = [
            'user' => ['User Package', 'User Plan', 'User Pricing', 'User Rate'],
            'admin' => ['Admin Package', 'Admin Plan', 'Admin Pricing', 'Admin Rate'],
            'both' => ['Package', 'Plan', 'Pricing', 'Rate', 'Universal Plan']
        ];

        $prefix = $this->faker->randomElement($prefixes[$userType]);
        $suffix = $this->faker->randomElement($suffixes[$userType]);

        return $prefix . ' ' . $suffix;
    }

    /**
     * Indicate that the pricing is for users
     */
    public function forUsers(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => 'user',
            'name' => $this->generateName('user'),
        ]);
    }

    /**
     * Indicate that the pricing is for admins
     */
    public function forAdmins(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => 'admin',
            'name' => $this->generateName('admin'),
        ]);
    }

    /**
     * Indicate that the pricing is for both users and admins
     */
    public function forBoth(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => 'both',
            'name' => $this->generateName('both'),
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

    /**
     * Create universal pricing for both users and admins
     */
    public function universal(): static
    {
        return $this->forBoth()->withRate(100.00, 75)->active();
    }
} 