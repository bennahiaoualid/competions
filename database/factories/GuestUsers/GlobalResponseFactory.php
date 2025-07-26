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
        return [
            'choice_id' =>Choice::factory(),
            'question_id' => GlobalQuestion::factory(),
            'user_id' => User::factory(),
            'score' => $this->faker->numberBetween(0, 100),
            'response_duration' => $this->faker->numberBetween(10, 100), // In seconds
        ];
    }
} 