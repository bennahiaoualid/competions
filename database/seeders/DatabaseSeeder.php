<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            RoleSeeder::class,
            //PaymentSeeder::class,
            SystemSettingSeeder::class,
            //UserSeeder::class,
            // You can add other seeders here as well
            // e.g., CompetitionSeeder::class,
            // LevelSeeder::class,
            CompetitionBulkSeeder::class,
        ]);

        // Example of creating a specific user if needed, can be removed or kept
        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
