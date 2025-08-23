<?php

namespace Database\Factories\Payment;

use App\Models\Payment\PaymentReviewRequest;
use App\Models\Payment\PaymentTransaction;
use App\Models\Admin\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentReviewRequestFactory extends Factory
{
    protected $model = PaymentReviewRequest::class;

    public function definition(): array
    {
        return [
            'payment_transaction_id' => PaymentTransaction::factory(),
            'request_reason' => $this->faker->sentence(),
            'status' => 'pending',
            'reviewed_by_admin_id' => null,
            'reviewed_at' => null,
            'review_observation' => null,
        ];
    }

    /**
     * Indicate that the review is pending
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'reviewed_by_admin_id' => null,
            'reviewed_at' => null,
            'review_observation' => null,
        ]);
    }

    /**
     * Indicate that the review is approved
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'reviewed_by_admin_id' => Admin::factory(),
            'reviewed_at' => now(),
            'review_observation' => $this->faker->optional()->sentence(),
        ]);
    }

    /**
     * Indicate that the review is rejected
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'reviewed_by_admin_id' => Admin::factory(),
            'reviewed_at' => now(),
            'review_observation' => $this->faker->sentence(),
        ]);
    }

    /**
     * Create review with specific reason
     */
    public function withReason(string $reason): static
    {
        return $this->state(fn (array $attributes) => [
            'request_reason' => $reason,
        ]);
    }

    /**
     * Create review with specific observation
     */
    public function withObservation(string $observation): static
    {
        return $this->state(fn (array $attributes) => [
            'review_observation' => $observation,
        ]);
    }

    /**
     * Create review for specific payment transaction
     */
    public function forTransaction(PaymentTransaction $transaction): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_transaction_id' => $transaction->id,
        ]);
    }

    /**
     * Create review reviewed by specific admin
     */
    public function reviewedBy(Admin $admin): static
    {
        return $this->state(fn (array $attributes) => [
            'reviewed_by_admin_id' => $admin->id,
            'reviewed_at' => now(),
        ]);
    }
} 