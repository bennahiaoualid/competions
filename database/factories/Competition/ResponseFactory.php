<?php

namespace Database\Factories\Competition;

use App\Models\Admin\Admin;
use App\Models\Competition\Question;
use App\Models\Competition\Response;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Competition\Response>
 */
class ResponseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Response::class;

    protected ?Question $providedQuestion = null;
    protected ?User $providedUser = null;
    protected ?Admin $providedAdmin = null;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $questionId = $this->providedQuestion ? $this->providedQuestion->id : Question::factory();
        $userId = $this->providedUser ? $this->providedUser->id : User::factory();
        $adminId = $this->providedAdmin ? $this->providedAdmin->id : null;

        return [
            'response_text' => $this->faker->realText(200),
            'question_id' => $questionId,
            'user_id' => $userId,
            'admin_id' => $adminId, // Or Admin::factory() if an admin should always be associated
            'score' => $this->faker->optional(0.7, 0)->randomFloat(2, 0, 20), // 70% chance of having a score, otherwise 0
            'response_duration' => $this->faker->numberBetween(10, 100), // Duration in seconds
        ];
    }

    /**
     * Indicate that the response has been scored by an admin.
     */
    public function scored(): static
    {
        // If a specific admin was provided via withAdmin, use that for scoring.
        // Otherwise, create a new admin specifically for this scored response.
        $adminForScoring = $this->providedAdmin ? $this->providedAdmin->id : Admin::factory();

        return $this->state(function (array $attributes) use ($adminForScoring) {
            // Determine max_score. If a question was provided, try to get its max_score.
            // Default to 20 if no specific question context is available.
            $maxScore = 20;
            if ($this->providedQuestion && $this->providedQuestion->max_score) {
                $maxScore = $this->providedQuestion->max_score;
            } elseif (isset($attributes['question_id'])) {
                // Attempt to fetch the question if only ID is available (e.g. from a prior state or direct set)
                $question = Question::find($attributes['question_id']);
                if ($question) {
                    $maxScore = $question->max_score;
                }
            }

            return [
                'admin_id' => $adminForScoring,
                'score' => $this->faker->randomFloat(2, 1, $maxScore),
            ];
        });
    }

    /**
     * Indicate a specific question to be used.
     *
     * @param  \App\Models\Competition\Question  $question
     * @return static
     */
    public function withQuestion(Question $question): static
    {
        $this->providedQuestion = $question;
        return $this;
    }

    /**
     * Indicate a specific user to be used.
     *
     * @param  \App\Models\User  $user
     * @return static
     */
    public function withUser(User $user): static
    {
        $this->providedUser = $user;
        return $this;
    }

    /**
     * Indicate a specific admin to be used for the response (e.g., for initial assignment before scoring).
     *
     * @param  \App\Models\Admin\Admin  $admin
     * @return static
     */
    public function withAdmin(Admin $admin): static
    {
        $this->providedAdmin = $admin;
        return $this;
    }
} 