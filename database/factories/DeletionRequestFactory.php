<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DeletionRequest>
 */
class DeletionRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'deletable_id' => 1,
            'deletable_type' => \App\Models\User::class,
            'deleted_by_admin_id' => 1,
            'snapshot_deleter_name' => 'Admin One',
            'approved_by_admin_id' => null,
            'snapshot_approver_name' => null,
            'snapshot_name' => fake()->name(),
            'snapshot_email' => fake()->email(),
            'reason' => fake()->sentence(),
            'status' => 'pending',
            'requested_at' => now(),
        ];
    }
}
