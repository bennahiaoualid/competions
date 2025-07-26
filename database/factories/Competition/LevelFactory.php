<?php

namespace Database\Factories\Competition;

use App\Models\Admin\Admin;
use App\Models\Competition\Competition;
use App\Models\Competition\Level;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Competition\Level>
 */
class LevelFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Level::class;

    protected ?Admin $providedAdmin = null;
    protected ?Competition $providedCompetition = null;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $adminId = null;
        if ($this->providedAdmin) {
            $adminId = $this->providedAdmin->id;
        } else {
            $existingAdmin = Admin::inRandomOrder()->first();
            $adminId = $existingAdmin ? $existingAdmin->id : Admin::factory();
        }

        $competitionId = null;
        if ($this->providedCompetition) {
            $competitionId = $this->providedCompetition->id;
        } else {
            $competitionId = Competition::factory();
        }

        return [
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence,
            'competition_id' => $competitionId,
            'admin_id' => $adminId, // Admin responsible for level questions
            'start_date' => $this->faker->dateTimeBetween('+1 week', '+2 months'),
            'duration' => $this->faker->numberBetween(30, 120), // Duration in minutes
            'questions_number' => $this->faker->numberBetween(5, 20),
            'status' => 'pending', 
        ];
    }

    /**
     * Indicate a specific admin to be used.
     *
     * @param  \App\Models\Admin\Admin  $admin
     * @return static
     */
    public function withAdmin(Admin $admin): static
    {
        $this->providedAdmin = $admin;
        return $this;
    }

    /**
     * Indicate a specific competition to be used.
     *
     * @param  \App\Models\Competition\Competition  $competition
     * @return static
     */
    public function withCompetition(Competition $competition): static
    {
        $this->providedCompetition = $competition;
        return $this;
    }
} 