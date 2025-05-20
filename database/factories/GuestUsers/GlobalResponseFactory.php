<?php

namespace Database\Factories\GuestUsers;

use App\Models\GuestUsers\Choice;
use App\Models\GuestUsers\GlobalQuestion;
use App\Models\GuestUsers\GlobalResponse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GuestUsers\GlobalResponse>
 */
class GlobalResponseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GlobalResponse::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // First, ensure a GlobalQuestion and its Choices are created
        $question = GlobalQuestion::factory()
            ->has(Choice::factory()->count(4)->sequence(
                ['correct' => true],
                ['correct' => false],
                ['correct' => false],
                ['correct' => false]
            ))
            ->create();

        // Select a choice (can be random or the correct one)
        $choice = $question->choices()->inRandomOrder()->first() ?? Choice::factory()->for($question)->create();

        return [
            'choice_id' => $choice->id,
            'question_id' => $question->id,
            'user_id' => User::factory(),
            // Score should ideally be based on whether the choice was correct and the question's score
            // For simplicity here, we'll assign a random score or derive it if choice is correct
            'score' => $choice->correct ? $question->score : $this->faker->numberBetween(0, $question->score / 2),
            'response_duration' => $this->faker->numberBetween(10, $question->duration), // In seconds
        ];
    }
} 