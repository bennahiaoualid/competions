# SafeDeleteAuditorJob Test Seeder

This seeder creates comprehensive test data to validate the `SafeDeleteAuditorJob` functionality.

## Overview

The `SafeDeleteAuditorTestSeeder` creates test data that simulates a real-world scenario where an admin auditor needs to be safely deleted from the system. It tests the job's ability to:

1. Reassign user auditing responsibilities to other admins
2. Remove the admin from active and pending competitions
3. Preserve the admin's audit history in finished competitions
4. Handle the `level_admin_user` pivot table correctly

## Test Data Created

### Admins
- **Target Admin** (`target.auditor@test.com`) - The admin that will be deleted
- **Test Admin One** (`admin1@test.com`) - Additional admin for testing
- **Test Admin Two** (`admin2@test.com`) - Additional admin for testing

### Users
- **User One** (`user1.target.admin@test.com`) - User created by target admin
- **User Two** (`user2.target.admin@test.com`) - User created by target admin

### Competitions
1. **Test Competition - Pending** (Status: `pending`)
   - Target admin is assigned as auditor
   - 2 levels with 3 questions each

2. **Test Competition - Active** (Status: `active`)
   - Target admin is NOT assigned as auditor (to test logic)
   - 2 levels with 3 questions each

3. **Test Competition - Finished** (Status: `finished`)
   - Target admin is assigned as auditor
   - 2 levels with 3 questions each
   - Contains responses that are audited by target admin

### Relationships Created
- Users are added to all competitions
- Target admin is auditor in pending and finished competitions only
- Target admin is assigned to audit users in finished competition levels
- Some responses in finished competition are audited by target admin

## Usage

### Running the Seeder

```bash
# Run the seeder to create test data
php artisan db:seed --class=SafeDeleteAuditorTestSeeder
```

### Testing the SafeDeleteAuditorJob

```bash
# Start Laravel Tinker
php artisan tinker

# Include the test script
include 'test_safe_delete_auditor.php';
```

### Manual Testing

```php
// Find the target admin
$targetAdmin = \App\Models\Admin\Admin::where('email', 'target.auditor@test.com')->first();

// Run the job
$job = new \App\Jobs\Admin\SafeDeleteAuditorJob($targetAdmin);
$job->handle();

// Verify results
// Check that target admin is removed from active/pending competitions
// Check that responses are reassigned to other admins
// Check that audit history in finished competitions is preserved
```

## Expected Behavior

After running the `SafeDeleteAuditorJob`:

### ✅ What Should Happen
1. **Active Competitions**: Target admin is removed as auditor
2. **Pending Competitions**: Target admin is removed as auditor  
3. **Finished Competitions**: Target admin remains as auditor (audit history preserved)
4. **Level Assignments**: Users assigned to target admin are reassigned to other available admins
5. **Responses**: Already audited responses keep the target admin's ID (audit trail preserved)

### ❌ What Should NOT Happen
1. Target admin should not be removed from finished competitions
2. Already audited responses should not lose their admin_id
3. Users should not be left without an assigned auditor in active competitions

## Verification Queries

After running the job, you can verify the results with these queries:

```php
// Check competitions where target admin is still auditor
$remainingAuditorCompetitions = \App\Models\Competition\Competition::whereHas('auditors', function($query) use ($targetAdmin) {
    $query->where('admins.id', $targetAdmin->id);
})->get();

// Check level assignments for target admin
$levelAssignments = DB::table('level_admin_user')->where('admin_id', $targetAdmin->id)->get();

// Check responses audited by target admin
$auditedResponses = \App\Models\Competition\Response::where('admin_id', $targetAdmin->id)->get();
```

## Cleanup

The seeder automatically cleans up previous test data before creating new data. To manually clean up:

```bash
# Run the seeder again (it will delete and recreate)
php artisan db:seed --class=SafeDeleteAuditorTestSeeder
```

## Database Tables Affected

The seeder and job work with these tables:
- `admins` - Admin records
- `users` - User records with admin_id
- `competitions` - Competition records
- `levels` - Level records
- `questions` - Question records
- `responses` - Response records with admin_id
- `admin_competition` - Pivot table for admin-competition auditor relationships
- `level_admin_user` - Pivot table for level-user-admin assignments

## Notes

- The seeder uses clear naming conventions for easy identification
- All test data is created with predictable patterns
- The seeder is idempotent - running it multiple times will clean up and recreate the same data
- The target admin email is `target.auditor@test.com` for easy identification 