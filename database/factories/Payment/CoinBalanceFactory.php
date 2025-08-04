<?php

namespace Database\Factories\Payment;

use App\Models\Payment\CoinBalance;
use App\Models\User;
use App\Models\Admin\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;

class CoinBalanceFactory extends Factory
{
    protected $model = CoinBalance::class;

    public function definition(): array
    {
        $balanceableTypes = [User::class, Admin::class];
        $balanceableType = $this->faker->randomElement($balanceableTypes);
        
        $totalEarned = $this->faker->numberBetween(100, 2000);
        $totalSpent = $this->faker->numberBetween(0, $totalEarned);
        $balance = $totalEarned - $totalSpent;
        
        return [
            'balanceable_id' => $balanceableType::factory(),
            'balanceable_type' => $balanceableType,
            'balance' => $balance,
            'total_earned' => $totalEarned,
            'total_spent' => $totalSpent,
        ];
    }

    /**
     * Create a balance with high coins
     */
    public function highBalance(): static
    {
        return $this->state(fn (array $attributes) => [
            'balance' => $this->faker->numberBetween(1000, 5000),
            'total_earned' => $this->faker->numberBetween(2000, 8000),
            'total_spent' => $this->faker->numberBetween(500, 3000),
        ]);
    }

    /**
     * Create a balance with low coins
     */
    public function lowBalance(): static
    {
        return $this->state(fn (array $attributes) => [
            'balance' => $this->faker->numberBetween(0, 100),
            'total_earned' => $this->faker->numberBetween(50, 500),
            'total_spent' => $this->faker->numberBetween(0, 400),
        ]);
    }

    /**
     * Create a balance with zero coins
     */
    public function zeroBalance(): static
    {
        return $this->state(fn (array $attributes) => [
            'balance' => 0,
            'total_earned' => $this->faker->numberBetween(100, 1000),
            'total_spent' => $this->faker->numberBetween(100, 1000),
        ]);
    }

    /**
     * Create a balance for a user
     */
    public function forUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'balanceable_id' => User::factory(),
            'balanceable_type' => User::class,
        ]);
    }

    /**
     * Create a balance for an admin
     */
    public function forAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'balanceable_id' => Admin::factory(),
            'balanceable_type' => Admin::class,
        ]);
    }
} 