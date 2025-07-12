<?php

namespace Database\Factories\Monitoring;

use App\Models\Monitoring\DeletionRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeletionRequest>
 */
class DeletionRequestFactory extends Factory
{
    protected $model = DeletionRequest::class;

    public function definition(): array
    {
        return [
            'deletable_id' => 1,
            'deletable_type' => \App\Models\Admin\Admin::class,
            'deleted_by_admin_id' => 1,
            'snapshot_deleter_name' => $this->faker->name(),
            'approved_by_admin_id' => null,
            'snapshot_approver_name' => null,
            'snapshot_name' => $this->faker->name(),
            'reason' => $this->faker->sentence(),
            'status' => 'pending',
            'requested_at' => now(),
        ];
    }
}
