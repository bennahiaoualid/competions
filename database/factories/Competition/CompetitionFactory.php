<?php

namespace Database\Factories\Competition;

use App\Models\Admin\Admin;
use App\Models\Competition\Competition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Competition\Competition>
 */
class CompetitionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Competition::class;

    protected ?Admin $providedAdmin = null;

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
            // Attempt to get a random existing admin
            $existingAdmin = Admin::inRandomOrder()->first();
            $adminId = $existingAdmin ? $existingAdmin->id : Admin::factory();
        }

        return [
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph,
            'admin_id' => $adminId,
            'start_date' => $this->faker->dateTimeBetween('+1 week', '+1 month'),
            'age_start' => $this->faker->numberBetween(10, 15),
            'age_end' => $this->faker->numberBetween(16, 25),
            'levels_number' => $this->faker->numberBetween(1, 5),
            'participants_sync_status' => 'completed',
            'last_synced_at' => now(),
            'status' => 'pending', // 'pending', 'active', 'finished'
            'is_suspended' => false,
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
} 