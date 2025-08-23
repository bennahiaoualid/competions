<?php

namespace Database\Factories\GuestUsers;

use App\Models\Admin\Admin;
use App\Models\GuestUsers\GlobalQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GuestUsers\GlobalQuestion>
 */
class GlobalQuestionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GlobalQuestion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_text' => $this->faker->realText(150) . '?',
            'score' => $this->faker->numberBetween(1, 10),
            'duration' => $this->faker->numberBetween(30, 90), // Duration in seconds
            'admin_id' => Admin::factory(), // The admin who created the question
            'approved' => null, // Don't create extra admins by default
            'deleted_admin_name' => null,
            'ai' => false,
            'user_id' => null,
        ];
    }

    /**
     * Indicate that the global question is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'approved' => Admin::factory(),
        ]);
    }

    /**
     * Indicate that the global question is not yet approved.
     */
    public function unapproved(): static
    {
        return $this->state(fn (array $attributes) => [
            'approved' => null,
        ]);
    }
} 