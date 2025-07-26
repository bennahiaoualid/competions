<?php

namespace Database\Factories\ProcessManagement;

use App\Models\ProcessManagement\DelayedProcess;
use App\Models\Admin\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Enums\ProcessTypeEnum;

class DelayedProcessFactory extends Factory
{
    protected $model = DelayedProcess::class;

    public function definition(): array
    {
        return [
            'process_type' => $this->faker->randomElement(ProcessTypeEnum::cases())->value,
            'target_type' => $this->faker->word(),
            'target_id' => $this->faker->randomNumber(),
            'initiator_id' => Admin::factory(),
            'context_data' => [
                'info' => $this->faker->sentence(),
                'details' => $this->faker->paragraph(),
            ],
            'check_period_hours' => $this->faker->numberBetween(1, 48),
            'created_at' => $this->faker->dateTimeBetween('-10 days', 'now'),
        ];
    }
} 