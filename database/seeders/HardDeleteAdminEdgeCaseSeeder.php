<?php

namespace Database\Seeders;

use App\Models\Admin\Admin;
use App\Models\Competition\Competition;
use App\Models\Competition\Level;
use App\Models\Competition\Question;
use App\Models\Competition\Response;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class HardDeleteAdminEdgeCaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->cleanup();
        return;

        // Create a transfer admin with 'owner' role
        $transferAdmin = Admin::factory()->create([
            'email' => 'hard_delete_transfer_admin@test.com',
            'name' => 'Hard Delete Transfer Admin',
        ]);
        $transferAdmin->assignRole('owner');

        // Create a soft-deleted admin to be deleted
        $adminToDelete = Admin::factory()->create([
            'email' => 'hard_delete_target_admin@test.com',
            'name' => 'Hard Delete Target Admin',
            'deleted_at' => now(),
        ]);

        // Suspended competitions owned by adminToDelete
        $suspendedCompetitions = Competition::factory()->count(2)->create([
            'title' => 'hard_delete_suspended_comp',
            'admin_id' => $adminToDelete->id,
            'is_suspended' => true,
        ]);
        // Finished competitions owned by adminToDelete
        $finishedCompetitions = Competition::factory()->count(2)->create([
            'title' => 'hard_delete_finished_comp',
            'admin_id' => $adminToDelete->id,
            'status' => Competition::STATUS_COMPLETED,
            'is_suspended' => false,
        ]);
        // Users created by adminToDelete
        $users = User::factory()->create([
            'name' => 'hard_delete_created_admin',
            'email' => 'hard_delete_user@test.com',
            'admin_id' => $adminToDelete->id,
        ]);
        // Admins created by adminToDelete
        $admins = Admin::factory()->create([
            'name' => 'hard_delete_created_admin',
            'email' => 'hard_delete_created_admin@test.com',
            'admin_id' => $adminToDelete->id,
        ]);

        $user = User::factory()->create([
            'name' => 'hard_delete_response_user',
            'email' => 'hard_delete_response_user@test.com',
        ]);
        // For each competition, create a level, question, and response
        foreach ([$suspendedCompetitions, $finishedCompetitions] as $competitionSet) {
            foreach ($competitionSet as $competition) {
                $level = Level::factory()->create([
                    'name' => 'hard_delete_level',
                    'competition_id' => $competition->id,
                    'admin_id' => $adminToDelete->id,
                    'status' => 'pending',
                    'start_date' => Carbon::now()->addDay(),
                ]);
                $question = Question::factory()->create([
                    'level_id' => $level->id,
                ]);
                Response::factory()->create([
                    'question_id' => $question->id,
                    'user_id' => $user->id,
                    'admin_id' => $adminToDelete->id,
                ]);
            }
        }

        // Create DeletionRequest for adminToDelete
        \App\Models\Monitoring\DeletionRequest::factory()->create([
            'deletable_id' => $adminToDelete->id,
            'deletable_type' => Admin::class,
            'deleted_by_admin_id' => $transferAdmin->id,
            'status' => 'pending',
            'requested_at' => now(),
        ]);
    }

    private function cleanup()
    {
        // Clean up DeletionRequest records for the test admin
        $adminId = Admin::withTrashed()->where('email', 'hard_delete_target_admin@test.com')->value('id');
        if ($adminId) {
            \App\Models\Monitoring\DeletionRequest::where('deletable_id', $adminId)
                ->where('deletable_type', Admin::class)
                ->delete();
        }else{
            \App\Models\Monitoring\DeletionRequest::where('snapshot_name', 'Hard Delete Target Admin')
            ->delete();
        }
        Admin::whereIn('email', [
            'hard_delete_transfer_admin@test.com',
            'hard_delete_target_admin@test.com',
            'hard_delete_created_admin@test.com',
        ])->forceDelete();

        User::whereIn('email', [
            'hard_delete_user@test.com',
            'hard_delete_response_user@test.com',
        ])->forceDelete();

        Competition::whereIn('title', [
            'hard_delete_suspended_comp',
            'hard_delete_finished_comp',
        ])->forceDelete();

        Level::where('name', 'hard_delete_level')->forceDelete();


    }
} 