<?php

namespace Database\Factories\Payment;

use App\Models\Payment\PaymentTransaction;
use App\Models\User;
use App\Models\Admin\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentTransactionFactory extends Factory
{
    protected $model = PaymentTransaction::class;

    public function definition(): array
    {
        $payableTypes = [User::class, Admin::class];
        $payableType = $this->faker->randomElement($payableTypes);
        
        return [
            'uuid' => $this->faker->uuid(),
            'payable_id' => $payableType::factory(),
            'payable_type' => $payableType,
            'approver_admin_id' => null,
            'approved_at' => null,
            'amount' => $this->faker->randomFloat(2, 100, 5000),
            'coins_credited' => $this->faker->numberBetween(10, 500),
            'payment_method' => $this->faker->randomElement(['cash', 'bank_transfer', 'mobile_money']),
            'proof_image_path' => 'payment_proofs/test_proof_' . $this->faker->uuid . '.jpg',
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected', 'cancelled']),
            'accountant_observation' => $this->faker->optional(0.3)->sentence(),
        ];
    }

    /**
     * Create a pending payment
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'approver_admin_id' => null,
            'approved_at' => null,
        ]);
    }

    /**
     * Create an approved payment
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    /**
     * Create a rejected payment
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'approved_at' => null,
            'accountant_observation' => $this->faker->sentence(),
        ]);
    }

    /**
     * Create a cancelled payment
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'approved_at' => null,
        ]);
    }

    /**
     * Create a cash payment
     */
    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'cash',
        ]);
    }

    /**
     * Create a bank transfer payment
     */
    public function bankTransfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'bank_transfer',
        ]);
    }

    /**
     * Create a mobile money payment
     */
    public function mobileMoney(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'mobile_money',
        ]);
    }
} 