<?php

namespace Database\Factories\Admin;

use App\Models\Admin\Admin;
use App\Models\Admin\AdminApproval;
use App\Models\Competition\Competition;
use App\Models\Competition\Level;
use App\Enums\AdminApprovalTypeEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Admin\AdminApproval>
 */
class AdminApprovalFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AdminApproval::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'admin_id' => Admin::factory(),
            'entity_type' => Competition::class,
            'entity_id' => Competition::factory(),
            'type' => $this->faker->randomElement([
                AdminApprovalTypeEnum::AUDITOR->value,
                AdminApprovalTypeEnum::LEVEL_MANAGER->value,
            ]),
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'updated_at' => function (array $attributes) {
                return $attributes['created_at'];
            },
        ];
    }

    /**
     * Indicate that the approval is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the approval is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
        ]);
    }

    /**
     * Indicate that the approval is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
        ]);
    }

    /**
     * Indicate that the approval is for auditor role.
     */
    public function auditor(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AdminApprovalTypeEnum::AUDITOR->value,
        ]);
    }

    /**
     * Indicate that the approval is for level manager role.
     */
    public function levelManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AdminApprovalTypeEnum::LEVEL_MANAGER->value,
        ]);
    }

    /**
     * Indicate that the approval is for a competition entity.
     */
    public function forCompetition(): static
    {
        return $this->state(fn (array $attributes) => [
            'entity_type' => Competition::class,
            'entity_id' => Competition::factory(),
        ]);
    }

    /**
     * Indicate that the approval is for a level entity.
     */
    public function forLevel(): static
    {
        return $this->state(fn (array $attributes) => [
            'entity_type' => Level::class,
            'entity_id' => Level::factory(),
        ]);
    }

    /**
     * Indicate that the approval is old (for testing cleanup).
     */
    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-30 days', '-25 hours'),
            'updated_at' => function (array $attributes) {
                return $attributes['created_at'];
            },
        ]);
    }

    /**
     * Indicate that the approval is recent (for testing cleanup).
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-23 hours', 'now'),
            'updated_at' => function (array $attributes) {
                return $attributes['created_at'];
            },
        ]);
    }
} 