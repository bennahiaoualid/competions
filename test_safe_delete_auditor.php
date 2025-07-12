<?php

/**
 * Test Script for SafeDeleteAuditorJob
 * 
 * This script demonstrates how to:
 * 1. Run the SafeDeleteAuditorTestSeeder to create test data
 * 2. Test the SafeDeleteAuditorJob functionality
 * 3. Verify the results
 * 
 * Usage:
 * php artisan tinker
 * include 'test_safe_delete_auditor.php';
 */

echo "=== SafeDeleteAuditorJob Test Script ===\n\n";

// Step 1: Run the seeder to create test data
echo "Step 1: Running SafeDeleteAuditorTestSeeder...\n";
Artisan::call('db:seed', ['--class' => 'SafeDeleteAuditorTestSeeder']);
echo "Seeder completed!\n\n";

// Step 2: Find the target admin
echo "Step 2: Finding target admin...\n";
$targetAdmin = \App\Models\Admin\Admin::where('email', 'target.auditor@test.com')->first();
if (!$targetAdmin) {
    echo "ERROR: Target admin not found!\n";
    exit;
}
echo "Target admin found: {$targetAdmin->name} (ID: {$targetAdmin->id})\n\n";

// Step 3: Show initial state
echo "Step 3: Initial state analysis...\n";
echo "Competitions where target admin is auditor:\n";
$auditorCompetitions = \App\Models\Competition\Competition::whereHas('auditors', function($query) use ($targetAdmin) {
    $query->where('admins.id', $targetAdmin->id);
})->get();

foreach ($auditorCompetitions as $competition) {
    echo "- {$competition->title} (Status: {$competition->status})\n";
}

echo "\nResponses audited by target admin:\n";
$auditedResponses = \App\Models\Competition\Response::where('admin_id', $targetAdmin->id)->get();
echo "Total responses audited: {$auditedResponses->count()}\n";

echo "\nLevel assignments for target admin:\n";
$levelAssignments = DB::table('level_admin_user')->where('admin_id', $targetAdmin->id)->get();
echo "Total level assignments: {$levelAssignments->count()}\n\n";

// Step 4: Run the SafeDeleteAuditorJob
echo "Step 4: Running SafeDeleteAuditorJob...\n";
$job = new \App\Jobs\Admin\SafeDeleteAuditorJob($targetAdmin);
$job->handle();
echo "Job completed!\n\n";

// Step 5: Show final state
echo "Step 5: Final state analysis...\n";

echo "Competitions where target admin is still auditor (should be empty for active/pending):\n";
$remainingAuditorCompetitions = \App\Models\Competition\Competition::whereHas('auditors', function($query) use ($targetAdmin) {
    $query->where('admins.id', $targetAdmin->id);
})->get();

foreach ($remainingAuditorCompetitions as $competition) {
    echo "- {$competition->title} (Status: {$competition->status})\n";
}

echo "\nLevel assignments for target admin (should be reassigned):\n";
$remainingLevelAssignments = DB::table('level_admin_user')->where('admin_id', $targetAdmin->id)->get();
echo "Remaining level assignments: {$remainingLevelAssignments->count()}\n";

echo "\nResponses still audited by target admin (should remain the same):\n";
$remainingAuditedResponses = \App\Models\Competition\Response::where('admin_id', $targetAdmin->id)->get();
echo "Remaining audited responses: {$remainingAuditedResponses->count()}\n\n";

// Step 6: Verify specific expectations
echo "Step 6: Verification...\n";

// Check that target admin is removed from active competitions
$activeCompetition = \App\Models\Competition\Competition::where('title', 'Test Competition - Active')->first();
$isStillAuditorInActive = $activeCompetition->auditors()->where('admins.id', $targetAdmin->id)->exists();
echo "Target admin removed from active competition: " . ($isStillAuditorInActive ? 'NO (ERROR)' : 'YES (CORRECT)') . "\n";

// Check that target admin is removed from pending competitions
$pendingCompetition = \App\Models\Competition\Competition::where('title', 'Test Competition - Pending')->first();
$isStillAuditorInPending = $pendingCompetition->auditors()->where('admins.id', $targetAdmin->id)->exists();
echo "Target admin removed from pending competition: " . ($isStillAuditorInPending ? 'NO (ERROR)' : 'YES (CORRECT)') . "\n";

// Check that target admin remains in finished competitions
$finishedCompetition = \App\Models\Competition\Competition::where('title', 'Test Competition - Finished')->first();
$isStillAuditorInFinished = $finishedCompetition->auditors()->where('admins.id', $targetAdmin->id)->exists();
echo "Target admin remains in finished competition: " . ($isStillAuditorInFinished ? 'YES (CORRECT)' : 'NO (ERROR)') . "\n";

// Check that responses are reassigned to other admins
$reassignedResponses = DB::table('level_admin_user')
    ->join('levels', 'level_admin_user.level_id', '=', 'levels.id')
    ->where('levels.competition_id', $activeCompetition->id)
    ->where('level_admin_user.admin_id', '!=', $targetAdmin->id)
    ->count();
echo "Responses reassigned to other admins: {$reassignedResponses}\n\n";

echo "=== Test completed! ===\n";

// Optional: Clean up test data
echo "\nTo clean up test data, run:\n";
echo "php artisan db:seed --class=SafeDeleteAuditorTestSeeder\n";
echo "(This will delete and recreate the test data)\n"; 