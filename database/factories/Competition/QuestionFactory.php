<?php

namespace Database\Factories\Competition;

use App\Models\Competition\Level;
use App\Models\Competition\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Competition\Question>
 */
class QuestionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Question::class;

    protected ?Level $providedLevel = null;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $levelId = $this->providedLevel ? $this->providedLevel->id : Level::factory();

        return [
            'question_text' => $this->faker->realText(100) . '?',
            'perfect_response' => $this->faker->realText(50), // Perfect answer for AI auditing
            'max_score' => $this->faker->numberBetween(5, 20),
            'duration' => $this->faker->numberBetween(30, 120), // Duration in seconds for a question
            'level_id' => $levelId,
        ];
    }

    /**
     * Indicate a specific level to be used.
     *
     * @param  \App\Models\Competition\Level  $level
     * @return static
     */
    public function withLevel(Level $level): static
    {
        $this->providedLevel = $level;
        return $this;
    }
} 