<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Admin\Admin;
use App\Models\GuestUsers\GlobalQuestion;
use App\Models\GuestUsers\Choice;
use Illuminate\Support\Facades\DB;

class GuestUserGlobalQuestionsSeeder extends Seeder
{
    // Unique marker for test data
    const TEST_EMAIL = 'guest_test_user@example.com';
    const ADMIN_EMAIL = 'admin_test_user@example.com';
    const QUESTION_MARKER = '[TEST_SEED]';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clean up before seeding
        $this->clean();

        // Create a test admin
        $admin = Admin::factory()->create([
            'email' => self::ADMIN_EMAIL,
            'name' => 'Test Admin',
        ]);

        // Create a guest user
        $guestUser = User::factory()->create([
            'email' => self::TEST_EMAIL,
            'name' => 'Guest Test User',
            'guest' => true,
            'admin_id' => $admin->id,
        ]);

        // Create 5 approved global questions, each with 4 choices (1 correct, 3 incorrect)
        for ($i = 1; $i <= 1; $i++) {
            $question = GlobalQuestion::factory()->create([
                'question_text' => self::QUESTION_MARKER . " What is the answer to test question $i?",
                'admin_id' => $admin->id,
                'approved' => $admin->id,
            ]);

            // Create 1 correct choice
            Choice::factory()->create([
                'question_id' => $question->id,
                'choice_text' => "Correct answer for Q$i",
                'correct' => true,
            ]);
            // Create 3 incorrect choices
            for ($j = 1; $j <= 3; $j++) {
                Choice::factory()->create([
                    'question_id' => $question->id,
                    'choice_text' => "Incorrect answer $j for Q$i",
                    'correct' => false,
                ]);
            }
        }
    }

    /**
     * Clean up all data created by this seeder.
     */
    public function clean(): void
    {
        // Delete choices for test questions
        $questionIds = GlobalQuestion::where('question_text', 'like', self::QUESTION_MARKER . '%')->pluck('id');
        Choice::whereIn('question_id', $questionIds)->forceDelete();
        // Delete test questions
        GlobalQuestion::whereIn('id', $questionIds)->forceDelete();
        // Delete test guest user
        User::where('email', self::TEST_EMAIL)->forceDelete();
        // Delete test admin
        Admin::where('email', self::ADMIN_EMAIL)->forceDelete();
    }
} 