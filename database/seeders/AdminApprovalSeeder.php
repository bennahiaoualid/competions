<?php

namespace Database\Seeders;

use App\Models\Admin\Admin;
use App\Models\Admin\AdminApproval;
use App\Models\Competition\Competition;
use App\Models\Competition\Level;
use App\Enums\AdminApprovalTypeEnum;
use Illuminate\Database\Seeder;

class AdminApprovalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->clean();
        $this->seedApprovals();
    }

    /**
     * Clean all existing approval records, competitions, and levels created by target admin
     */
    private function clean(): void
    {
        // Get target admin
        $targetAdmin = Admin::where('email', 'oualidbennahia@gmail.com')->first();
        
        if ($targetAdmin) {
            // Delete competitions created by target admin
            $competitionsDeleted = Competition::where('admin_id', $targetAdmin->id)->delete();
            
            // Delete levels created by target admin
            $levelsDeleted = Level::where('admin_id', $targetAdmin->id)->delete();
            
            $this->command->info("Cleaned {$competitionsDeleted} competitions and {$levelsDeleted} levels created by target admin.");
        }
        
        // Clean all approval records
        AdminApproval::truncate();
        $this->command->info('Cleaned all admin approval records.');
    }

    /**
     * Seed approval records
     */
    private function seedApprovals(): void
    {
        // Get the specific admin or first registered admin
        $targetAdmin = Admin::where('email', 'oualidbennahia@gmail.com')->first();
        
        if (!$targetAdmin) {
            $targetAdmin = Admin::orderBy('created_at', 'asc')->first();
        }

        if (!$targetAdmin) {
            $this->command->warn('No admin found. Please run admin seeder first.');
            return;
        }

        $this->command->info('Using admin: ' . $targetAdmin->name . ' (' . $targetAdmin->email . ')');

        // Create 1 competition
        $competition = Competition::factory()->withAdmin($targetAdmin)->create();
        
        // Create 1 level for the competition
        $level = Level::factory()
            ->withCompetition($competition)
            ->withAdmin($targetAdmin)
            ->create();

        $this->command->info('Created 1 competition and 1 level.');

        $approvals = [];

        // Create 1 pending auditor approval for competition
        $approvals[] = [
            'admin_id' => $targetAdmin->id,
            'entity_type' => Competition::class,
            'entity_id' => $competition->id,
            'type' => AdminApprovalTypeEnum::AUDITOR->value,
            'status' => 'pending',
            'created_at' => now()->subDays(rand(1, 30)),
            'updated_at' => now()->subDays(rand(1, 30)),
        ];
        
        // Create 1 pending level manager approval for level
        $approvals[] = [
            'admin_id' => $targetAdmin->id,
            'entity_type' => Level::class,
            'entity_id' => $level->id,
            'type' => AdminApprovalTypeEnum::LEVEL_MANAGER->value,
            'status' => 'pending',
            'created_at' => now()->subDays(rand(1, 30)),
            'updated_at' => now()->subDays(rand(1, 30)),
        ];

        // Insert all approvals
        AdminApproval::insert($approvals);

        $this->command->info('Created ' . count($approvals) . ' admin approval records.');
        $this->command->info('Status distribution:');
        $this->command->info('- Pending: ' . AdminApproval::where('status', 'pending')->count());
        $this->command->info('- Approved: ' . AdminApproval::where('status', 'approved')->count());
        $this->command->info('- Rejected: ' . AdminApproval::where('status', 'rejected')->count());
    }


} 