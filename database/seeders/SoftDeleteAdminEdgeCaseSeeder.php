<?php

namespace Database\Seeders;

use App\Models\Admin\Admin;
use App\Models\Admin\AdminAvailability;
use App\Models\Competition\Competition;
use App\Models\Competition\Level;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class SoftDeleteAdminEdgeCaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->cleanup();
        //return;
        // 1. Only auditor in active competition (should block)
        $adminOnlyAuditorActive = Admin::factory()->create([
            'email' => 'admin_only_auditor_active@test.com',
            'name' => 'Admin Only Auditor Active',
        ]);
        AdminAvailability::updateOrCreate(
            ['admin_id' => $adminOnlyAuditorActive->id],
            [
                'auditor' => true,
                'level_manager' => true,
                'ownership_transfer' => true,
            ]
        );
        $ownerAdminOnlyAuditorActive = Admin::factory()->create([
            'email' => 'owner_admin_only_auditor_active@test.com',
            'name' => 'Owner Admin Only Auditor Active',
        ]);
        AdminAvailability::updateOrCreate(
            ['admin_id' => $ownerAdminOnlyAuditorActive->id],
            [
                'auditor' => true,
                'level_manager' => true,
                'ownership_transfer' => true,
            ]
        );
        $compOnlyAuditorActive = Competition::factory()->create([
            'title' => 'competition_admin_only_auditor_active',
            'admin_id' => $ownerAdminOnlyAuditorActive->id,
            'status' => Competition::STATUS_ACTIVE,
            'start_date' => Carbon::now()->subDay(),
        ]);
        $compOnlyAuditorActive->auditors()->attach($adminOnlyAuditorActive->id);

        // 2. Only auditor in pending competition (should block)
        $adminOnlyAuditorPending = Admin::factory()->create([
            'email' => 'admin_only_auditor_pending@test.com',
            'name' => 'Admin Only Auditor Pending',
        ]);
        AdminAvailability::updateOrCreate(
            ['admin_id' => $adminOnlyAuditorPending->id],
            [
                'auditor' => true,
                'level_manager' => true,
                'ownership_transfer' => true,
            ]
        );
        $userOnlyAuditorPending = User::factory()->create([
            'email' => 'user_only_auditor_pending@test.com',
            'name' => 'User Only Auditor Pending',
        ]);
        $compOnlyAuditorPending = Competition::factory()->create([
            'title' => 'competition_delete_admin_only_auditor_pending',
            'admin_id' => $adminOnlyAuditorPending->id,
            'status' => Competition::STATUS_PENDING,
            'start_date' => Carbon::now()->addDay(),
        ]);
        $compOnlyAuditorPending->auditors()->attach($adminOnlyAuditorPending->id);

        // 3. Multiple auditors in active competition (should succeed)
        $adminMultiAuditor = Admin::factory()->create([
            'email' => 'admin_multi_auditor@test.com',
            'name' => 'Admin Multi Auditor',
        ]);
        AdminAvailability::updateOrCreate(
            ['admin_id' => $adminMultiAuditor->id],
            [
                'auditor' => true,
                'level_manager' => true,
                'ownership_transfer' => true,
            ]
        );
        $userMultiAuditor = User::factory()->create([
            'email' => 'user_multi_auditor@test.com',
            'name' => 'User Multi Auditor',
        ]);
        $compMultiAuditor = Competition::factory()->create([
            'title' => 'competition_delete_admin_multi_auditor',
            'admin_id' => $adminMultiAuditor->id,
            'status' => Competition::STATUS_ACTIVE,
            'start_date' => Carbon::now()->subDay(),
        ]);
        // Add two auditors
        $compMultiAuditor->auditors()->attach([$adminMultiAuditor->id, $adminOnlyAuditorActive->id]);

        // 4, 5, 6, 7: Use the same target admin for all these cases
        $targetAdmin = Admin::factory()->create([
            'email' => 'admin_soft_delete_target@test.com',
            'name' => 'Admin Soft Delete Target',
        ]);
        AdminAvailability::updateOrCreate(
            ['admin_id' => $targetAdmin->id],
            [
                'auditor' => true,
                'level_manager' => true,
                'ownership_transfer' => true,
            ]
        );

        // 4. Owns active competition with running level (should succeed)
        $compOwnsActiveRunning = Competition::factory()->create([
            'title' => 'competition_delete_admin_owns_active_running_level',
            'admin_id' => $targetAdmin->id,
            'status' => Competition::STATUS_ACTIVE,
            'start_date' => Carbon::now()->subDay(),
        ]);
        $levelOwnsActiveRunning = Level::factory()->create([
            'name' => 'level_owns_active_running',
            'competition_id' => $compOwnsActiveRunning->id,
            'admin_id' => $targetAdmin->id,
            'status' => Level::STATUS_PENDING, // Not active, so should not block
            'start_date' => Carbon::now()->addDay(),
        ]);

        // 5. Responsible for level in another's competition (should succeed)
        $adminOtherOwner = Admin::factory()->create([
            'email' => 'admin_other_owner@test.com',
            'name' => 'Admin Other Owner',
        ]);
        AdminAvailability::updateOrCreate(
            ['admin_id' => $adminOtherOwner->id],
            [
                'auditor' => true,
                'level_manager' => true,
                'ownership_transfer' => true,
            ]
        );
        $compLevelInOthers = Competition::factory()->create([
            'title' => 'competition_delete_admin_level_in_others_comp',
            'admin_id' => $adminOtherOwner->id,
            'status' => Competition::STATUS_ACTIVE,
            'start_date' => Carbon::now()->subDay(),
        ]);
        $levelInOthers = Level::factory()->create([
            'name' => 'level_in_others_comp',
            'competition_id' => $compLevelInOthers->id,
            'admin_id' => $targetAdmin->id,
            'status' => Level::STATUS_PENDING,
            'start_date' => Carbon::now()->addDay(),
        ]);

        // 6. Responsible for level in own competition (should succeed)
        $compLevelInOwn = Competition::factory()->create([
            'title' => 'competition_delete_admin_level_in_own_comp',
            'admin_id' => $targetAdmin->id,
            'status' => Competition::STATUS_ACTIVE,
            'start_date' => Carbon::now()->subDay(),
        ]);
        $levelInOwn = Level::factory()->create([
            'name' => 'level_in_own_comp',
            'competition_id' => $compLevelInOwn->id,
            'admin_id' => $targetAdmin->id,
            'status' => Level::STATUS_PENDING,
            'start_date' => Carbon::now()->addDay(),
        ]);

        // 7. Everything OK (should succeed)
        $compOk = Competition::factory()->create([
            'title' => 'competition_delete_admin_ok',
            'admin_id' => $targetAdmin->id,
            'status' => Competition::STATUS_COMPLETED,
            'start_date' => Carbon::now()->subDays(10),
        ]);

        // Edge: Admin owns active competition with running level (should block)
        $adminOwnsActiveRunningBlock = Admin::factory()->create([
            'email' => 'admin_owns_active_running_level_block@test.com',
            'name' => 'Admin Owns Active Running Level Block',
        ]);
        AdminAvailability::updateOrCreate(
            ['admin_id' => $adminOwnsActiveRunningBlock->id],
            [
                'auditor' => true,
                'level_manager' => true,
                'ownership_transfer' => true,
            ]
        );
        $compOwnsActiveRunningBlock = Competition::factory()->create([
            'title' => 'competition_delete_admin_owns_active_running_level_block',
            'admin_id' => $adminOwnsActiveRunningBlock->id,
            'status' => Competition::STATUS_ACTIVE,
            'start_date' => Carbon::now()->subDay(),
        ]);
        $levelOwnsActiveRunningBlock = Level::factory()->create([
            'name' => 'level_owns_active_running_block',
            'competition_id' => $compOwnsActiveRunningBlock->id,
            'admin_id' => $adminOwnsActiveRunningBlock->id,
            'status' => Level::STATUS_ACTIVE, // This is the key: level is active!
            'start_date' => Carbon::now()->subHour(),
        ]);
    }

    private function cleanup()
    {
        // Clean up AdminAvailability for all relevant admins
        $adminEmails = [
            'admin_only_auditor_active@test.com',
            'admin_only_auditor_pending@test.com',
            'owner_admin_only_auditor_active@test.com',
            'admin_multi_auditor@test.com',
            'admin_soft_delete_target@test.com',
            'admin_other_owner@test.com',
            'admin_owns_active_running_level_block@test.com',
        ];
        $adminIds = Admin::whereIn('email', $adminEmails)->pluck('id');
        \App\Models\Admin\AdminAvailability::whereIn('admin_id', $adminIds)->delete();

        Admin::whereIn('email', [
            'admin_only_auditor_active@test.com',
            'admin_only_auditor_pending@test.com',
            'owner_admin_only_auditor_active@test.com',
            'admin_multi_auditor@test.com',
            'admin_soft_delete_target@test.com',
            'admin_other_owner@test.com',
            'admin_owns_active_running_level_block@test.com',
        ])->forceDelete();

        User::whereIn('email', [
            'user_only_auditor_active@test.com',
            'user_only_auditor_pending@test.com',
            'user_multi_auditor@test.com',
        ])->forceDelete();

        Competition::whereIn('title', [
            'competition_delete_admin_only_auditor_active',
            'competition_delete_admin_only_auditor_pending',
            'owner_admin_only_auditor_active',
            'competition_delete_admin_multi_auditor',
            'competition_delete_admin_owns_active_running_level',
            'competition_delete_admin_level_in_others_comp',
            'competition_delete_admin_level_in_own_comp',
            'competition_delete_admin_ok',
            'competition_delete_admin_owns_active_running_level_block',
        ])->forceDelete();

        Level::whereIn('name', [
            'level_owns_active_running',
            'level_in_others_comp',
            'level_in_own_comp',
            'level_owns_active_running_block',
        ])->forceDelete();
    }
} 