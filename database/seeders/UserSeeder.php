<?php

namespace Database\Seeders;

use App\Models\Admin\Admin;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $existingAdmins = Admin::all();

        if ($existingAdmins->isEmpty()) {
            $this->command->info('No existing admins found. Cannot create users linked to existing admins.');
            // Optionally, create some admins here if that's the desired fallback
            // Admin::factory(5)->create();
            // $existingAdmins = Admin::all();
            // if ($existingAdmins->isEmpty()) {
            //     $this->command->error('Failed to create fallback admins. Aborting UserSeeder.');
            //     return;
            // }
            return; // Or proceed to create users with new admins if UserFactory handles that
        }

        $this->command->info('Seeding 300 users with existing admins...');
        
        for ($i = 0; $i < 300; $i++) {
            $randomAdmin = $existingAdmins->random();
            User::factory()->withAdmin($randomAdmin)->create();
        }

        $this->command->info('Successfully seeded 300 users.');
    }
}
