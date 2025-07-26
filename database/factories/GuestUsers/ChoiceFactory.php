<?php

namespace Database\Factories\GuestUsers;

use App\Models\GuestUsers\Choice;
use App\Models\GuestUsers\GlobalQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GuestUsers\Choice>
 */
class ChoiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Choice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'choice_text' => $this->faker->sentence(4),
            'correct' => $this->faker->boolean(25), // 25% chance of being the correct answer
            'question_id' => GlobalQuestion::factory(),
        ];
    }

    /**
     * Indicate that the choice is the correct one.
     */
    public function correct(): static
    {
        return $this->state(fn (array $attributes) => [
            'correct' => true,
        ]);
    }

    /**
     * Indicate that the choice is incorrect.
     */
    public function incorrect(): static
    {
        return $this->state(fn (array $attributes) => [
            'correct' => false,
        ]);
    }
} 