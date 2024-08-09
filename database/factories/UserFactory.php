<?php

namespace Database\Factories;

use App\Models\Admin\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $counter = 1;

        // Generate random admin id from the admins table
        $admin = Admin::inRandomOrder()->first();
        $admin_id = $admin ? $admin->id : 1;

        // Generate the name and email based on the counter
        $name = 'user_' . $counter . '_' . $this->faker->firstName;
        $email = strtolower($name) . '@user.com';

        $user = [
            'name' => $name,
            'email' => $email,
            'admin_id' => $admin_id,
            'birthdate' => $this->faker->dateTimeBetween('2002-01-01', '2014-01-01')->format('Y-m-d'),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'email_verified_at' => $this->faker->optional()->dateTime(),
            'password' => bcrypt('12345678'), // password
            'remember_token' => Str::random(10),
        ];

        $counter++;

        return $user;
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
