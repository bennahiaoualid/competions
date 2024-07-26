<?php

namespace Database\Seeders;

use App\Models\Admin\Admin;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeders.
     */
    public function run(): void
    {
        DB::table('admins')->insert([
            'name' => "oualid",
            'email' => "oualidbennahia@gmail.com",
            'birthdate' => "1999-11-12",
            'gender' => "male",
            'password' => Hash::make('12345678'),
        ]);
       // Admin::factory(10000)->create();
    }
}
