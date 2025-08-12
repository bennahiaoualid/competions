<?php

namespace Database\Factories\Payment;

use App\Models\Payment\PaymentAuditLog;
use App\Models\Payment\PaymentTransaction;
use App\Models\Admin\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentAuditLogFactory extends Factory
{
    protected $model = PaymentAuditLog::class;

    public function definition(): array
    {
        $actions = ['created', 'approved', 'rejected', 'cancelled', 'updated'];
        $action = $this->faker->randomElement($actions);
        
        return [
            'payment_transaction_id' => PaymentTransaction::factory(),
            'admin_id' => Admin::factory(),
            'action' => $action,
            'old_values' => $this->faker->optional(0.7)->json(),
            'new_values' => $this->faker->optional(0.7)->json(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
        ];
    }

    /**
     * Create an audit log for payment creation
     */
    public function created(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'created',
            'old_values' => null,
            'new_values' => json_encode([
                'amount' => $this->faker->randomFloat(2, 100, 5000),
                'payment_method' => $this->faker->randomElement(['cash', 'bank_transfer', 'mobile_money']),
                'status' => 'pending'
            ]),
        ]);
    }

    /**
     * Create an audit log for payment approval
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'approved',
            'old_values' => json_encode(['status' => 'pending']),
            'new_values' => json_encode([
                'status' => 'approved',
                'approved_at' => now()->toISOString(),
                'accountant_observation' => $this->faker->optional(0.5)->sentence()
            ]),
        ]);
    }

    /**
     * Create an audit log for payment rejection
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'rejected',
            'old_values' => json_encode(['status' => 'pending']),
            'new_values' => json_encode([
                'status' => 'rejected',
                'accountant_observation' => $this->faker->sentence()
            ]),
        ]);
    }

    /**
     * Create an audit log for payment cancellation
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'cancelled',
            'old_values' => json_encode(['status' => 'pending']),
            'new_values' => json_encode(['status' => 'cancelled']),
        ]);
    }

    /**
     * Create an audit log for payment update
     */
    public function updated(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'updated',
            'old_values' => json_encode([
                'amount' => $this->faker->randomFloat(2, 100, 2000),
                'payment_method' => 'cash'
            ]),
            'new_values' => json_encode([
                'amount' => $this->faker->randomFloat(2, 2000, 5000),
                'payment_method' => 'bank_transfer'
            ]),
        ]);
    }
} 