# Approval Service Documentation

## Overview

The Approval Service is a flexible system for managing admin approval requests for various entities in the application. It follows a factory pattern with handlers for different approval types, making it easy to extend with new approval types.

## Architecture

### Core Components

1. **AdminApprovalTypeEnum** - Defines available approval types
2. **ApprovalHandlerInterface** - Contract for approval handlers
3. **ApprovalHandlerFactory** - Creates appropriate handlers based on approval type
4. **ApprovalAssignmentService** - Main service for processing approvals
5. **AdminApprovalService** - Manages approval requests
6. **OptimizedCompetitionNotificationService** - Handles notifications for approval decisions

### Current Approval Types

- `AUDITOR` - Assign admin as auditor for competition
- `LEVEL_MANAGER` - Assign admin as level manager for competition level

## Adding a New Approval Type

### Step 1: Update the Enum

Add your new approval type to `app/Enums/AdminApprovalTypeEnum.php`:

```php
enum AdminApprovalTypeEnum: string
{
    case AUDITOR = 'auditor';
    case LEVEL_MANAGER = 'level_manager';
    case YOUR_NEW_TYPE = 'your_new_type'; // Add your new type here

    public function label(): string
    {
        return match($this) {
            self::AUDITOR => __('admin.admin_approval.types.auditor'),
            self::LEVEL_MANAGER => __('admin.admin_approval.types.level_manager'),
            self::YOUR_NEW_TYPE => __('admin.admin_approval.types.your_new_type'), // Add label
        };
    }

    public function getDescription(): string
    {
        return match($this) {
            self::AUDITOR => __('admin.admin_approval.descriptions.auditor'),
            self::LEVEL_MANAGER => __('admin.admin_approval.descriptions.level_manager'),
            self::YOUR_NEW_TYPE => __('admin.admin_approval.descriptions.your_new_type'), // Add description
        };
    }

    public function getDetailUrl(int $entityId): array
    {
        return match($this) {
            self::AUDITOR => [
                'name' => 'admin.competitions.edit',
                'params' => ['id' => base64_encode($entityId)]
            ],
            self::LEVEL_MANAGER => [
                'name' => 'admin.competitions.level.edit',
                'params' => ['id' => base64_encode($entityId)]
            ],
            self::YOUR_NEW_TYPE => [
                'name' => 'admin.your.entity.edit', // Add your route
                'params' => ['id' => base64_encode($entityId)]
            ],
        };
    }
}
```

### Step 2: Create Handler

Create a new handler in `app/Services/Approval/Handlers/YourNewTypeApprovalHandler.php`:

```php
<?php

namespace App\Services\Approval\Handlers;

use App\Contracts\Approval\ApprovalHandlerInterface;
use App\Models\Admin\AdminApproval;
use App\Enums\AdminApprovalTypeEnum;
use App\Services\YourService\YourService;
use App\Services\Notification\OptimizedCompetitionNotificationService;

class YourNewTypeApprovalHandler implements ApprovalHandlerInterface
{
    public function __construct(
        private YourService $yourService,
        private OptimizedCompetitionNotificationService $notificationService
    ) {}

    public function handle(AdminApproval $approval): bool
    {
        $entity = $approval->entity;
        
        // Implement your approval logic here
        $success = $this->yourService->assignYourRole($entity, $approval->admin_id);
        
        if ($success) {
            // Notify about approval decision
            $this->notificationService->approvalDecision($approval, 'approved');
        }
        
        return $success;
    }

    public function canHandle(AdminApprovalTypeEnum $type): bool
    {
        return $type === AdminApprovalTypeEnum::YOUR_NEW_TYPE;
    }
}
```

### Step 3: Update Factory

Add your handler to `app/Factories/Approval/ApprovalHandlerFactory.php`:

```php
public function createHandler(AdminApprovalTypeEnum $type): ApprovalHandlerInterface
{
    return match($type) {
        AdminApprovalTypeEnum::AUDITOR => new AuditorApprovalHandler($this->competitionService, $this->notificationService),
        AdminApprovalTypeEnum::LEVEL_MANAGER => new LevelManagerApprovalHandler($this->levelService, $this->notificationService),
        AdminApprovalTypeEnum::YOUR_NEW_TYPE => new YourNewTypeApprovalHandler($this->yourService, $this->notificationService), // Add your handler
    };
}
```

### Step 4: Add Translations

#### English (`lang/en/admin.php`)

```php
'admin_approval' => [
    'types' => [
        'auditor' => 'Auditor',
        'level_manager' => 'Level Manager',
        'your_new_type' => 'Your New Type', // Add your type
    ],
    'descriptions' => [
        'auditor' => 'Assign admin as auditor for competition',
        'level_manager' => 'Assign admin as level manager for competition level',
        'your_new_type' => 'Assign admin as your new role for entity', // Add description
    ],
],
```

#### Arabic (`lang/ar/admin.php`)

```php
'admin_approval' => [
    'types' => [
        'auditor' => 'مدقق',
        'level_manager' => 'مدير مستوى',
        'your_new_type' => 'نوعك الجديد', // Add your type
    ],
    'descriptions' => [
        'auditor' => 'تعيين المسؤول كمدقق للمسابقة',
        'level_manager' => 'تعيين المسؤول كمدير مستوى لمستوى المسابقة',
        'your_new_type' => 'تعيين المسؤول كدورك الجديد للكيان', // Add description
    ],
],
```

### Step 5: Add Notification Support

If your approval type needs notifications, add the notification method to `OptimizedCompetitionNotificationService`:

```php
/**
 * Notify about your new approval type
 */
public function yourNewTypeRequested(YourEntity $entity, Admin $admin): void
{
    $this->notifyUsers(collect([$admin]), $entity->competition, 'your_new_type_requested', null, [
        'entity_id' => $entity->id,
        'entity_name' => $entity->name,
    ]);
}
```

## Usage Examples

### Creating an Approval Request

```php
use App\Services\Admin\AdminApprovalService;
use App\Enums\AdminApprovalTypeEnum;

$approvalService = app(AdminApprovalService::class);

// Create approval request for auditor
$admin = $approvalService->createApprovalRequest(
    adminId: $admin->id,
    entityType: Competition::class,
    entityId: $competition->id,
    type: AdminApprovalTypeEnum::AUDITOR
);
```

### Processing an Approval

```php
use App\Services\Approval\ApprovalAssignmentService;

$assignmentService = app(ApprovalAssignmentService::class);

// Approve request
$success = $assignmentService->approveRequest($approvalId);

// Reject request
$success = $assignmentService->rejectRequest($approvalId, 'Reason for rejection');
```

### Checking Approval Status

```php
$status = $approvalService->getApprovalStatus(
    adminId: $admin->id,
    entityType: Competition::class,
    entityId: $competition->id,
    type: AdminApprovalTypeEnum::AUDITOR->value
);

// Returns: ['pending' => 1, 'approved' => 0, 'rejected' => 0]
```

## Database Schema

The `admin_approvals` table structure:

```sql
CREATE TABLE admin_approvals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id BIGINT UNSIGNED NOT NULL,
    entity_type VARCHAR(255) NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(255) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_admin_approvals_admin_id (admin_id),
    INDEX idx_admin_approvals_entity (entity_type, entity_id),
    INDEX idx_admin_approvals_type (type),
    INDEX idx_admin_approvals_status (status)
);
```

## Model Relationships

```php
// AdminApproval Model
public function admin(): BelongsTo
{
    return $this->belongsTo(Admin::class);
}

public function entity(): MorphTo
{
    return $this->morphTo();
}
```

## Available Scopes

```php
// Get approvals for specific admin
AdminApproval::forAdmin($adminId)->get();

// Get approvals by status
AdminApproval::byStatus('pending')->get();

// Get approvals by type
AdminApproval::byType(AdminApprovalTypeEnum::AUDITOR->value)->get();

// Get pending approvals
AdminApproval::pending()->get();

// Get approved requests
AdminApproval::approved()->get();

// Get rejected requests
AdminApproval::rejected()->get();
```

## Notification Integration

The approval system integrates with the `OptimizedCompetitionNotificationService` for sending notifications about approval decisions:

- **Database notifications** - Stored in the database for later viewing
- **Broadcast notifications** - Real-time notifications for immediate feedback
- **Email notifications** - Optional email notifications for important events

### Notification Events

- `approval_approved` - When an approval request is approved
- `approval_rejected` - When an approval request is rejected

### Notification Data Structure

```php
[
    'translation_key' => 'notifications.approval.approved',
    'translation_data' => [
        'competition_title' => $competition->title,
        'admin_name' => $admin->name,
        'approval_type' => $approval->type,
    ],
    'notification_priority_type' => 'success',
    'link' => $approvalLink,
    'competition_id' => $competition->id,
    'event_type' => 'approval_approved',
    'type' => 'approval_decision',
    'recipient_type' => 'competition_creator',
    'recipient_id' => $competition->admin_id,
]
```

## Testing

### Unit Tests

Create tests for your new handler:

```php
<?php

namespace Tests\Unit\Services\Approval\Handlers;

use Tests\TestCase;
use App\Services\Approval\Handlers\YourNewTypeApprovalHandler;
use App\Models\Admin\AdminApproval;
use App\Enums\AdminApprovalTypeEnum;

class YourNewTypeApprovalHandlerTest extends TestCase
{
    public function test_can_handle_your_new_type(): void
    {
        $handler = new YourNewTypeApprovalHandler(
            $this->mock(YourService::class),
            $this->mock(OptimizedCompetitionNotificationService::class)
        );

        $this->assertTrue($handler->canHandle(AdminApprovalTypeEnum::YOUR_NEW_TYPE));
    }

    public function test_handle_approval_successfully(): void
    {
        // Test your handler logic
    }
}
```

### Feature Tests

Test the complete approval flow:

```php
public function test_approval_workflow(): void
{
    // Create approval request
    $approval = AdminApproval::factory()->create([
        'type' => AdminApprovalTypeEnum::YOUR_NEW_TYPE->value,
        'status' => 'pending'
    ]);

    // Process approval
    $assignmentService = app(ApprovalAssignmentService::class);
    $success = $assignmentService->approveRequest($approval->id);

    $this->assertTrue($success);
    $this->assertEquals('approved', $approval->fresh()->status);
}
```

## Best Practices

1. **Always implement the interface** - Ensure your handler implements `ApprovalHandlerInterface`
2. **Use dependency injection** - Inject required services in your handler constructor
3. **Handle failures gracefully** - Return `false` if the approval operation fails
4. **Send notifications** - Use the notification service to inform relevant parties
5. **Add translations** - Always provide both English and Arabic translations
6. **Write tests** - Create comprehensive tests for your new approval type
7. **Document your changes** - Update this README when adding new approval types
8. **Respect status transitions** - Only pending approvals can be approved/rejected
9. **Handle duplicates gracefully** - Use `insertOrIgnore()` for bulk operations
10. **Monitor cleanup logs** - Check logs for cleanup activity and issues

## Database Constraints

The approval system includes several database constraints to ensure data integrity:

### Unique Constraint
- **Purpose**: Prevents duplicate pending approvals for the same admin/entity/type
- **Constraint**: `unique_pending_approval` on `(admin_id, entity_type, entity_id, type)`
- **Effect**: Duplicate approval requests are ignored gracefully

### Status Validation
- **Pending → Approved**: Only pending approvals can be approved
- **Pending → Rejected**: Only pending approvals can be rejected
- **Validation**: Throws `InvalidArgumentException` for invalid transitions

### Cleanup Constraints
- **Time-based**: All approvals older than 24 hours are cleaned up
- **Status-agnostic**: Cleans pending, approved, and rejected records
- **Scheduled**: Runs every 6 hours via Laravel scheduler

## Troubleshooting

### Common Issues

1. **Handler not found** - Ensure your handler is registered in the factory
2. **Translation missing** - Check that translations exist in both language files
3. **Notification not sent** - Verify the notification service is properly injected
4. **Permission denied** - Check that the admin has permission to approve the request
5. **Duplicate constraint violation** - Use `insertOrIgnore()` for bulk operations
6. **Invalid status transition** - Only approve/reject pending approvals
7. **Cleanup not running** - Check scheduler configuration and logs

### Debug Tips

```php
// Check if handler can handle the type
$handler = app(ApprovalHandlerFactory::class)->createHandler($approvalType);
dd($handler->canHandle($approvalType));

// Check approval status
$approval = AdminApproval::find($approvalId);
dd($approval->status, $approval->type);

// Check notification data
dd($notificationData);
```

## Migration Guide

When adding a new approval type, follow this checklist:

- [ ] Add enum value to `AdminApprovalTypeEnum`
- [ ] Create handler class implementing `ApprovalHandlerInterface`
- [ ] Register handler in `ApprovalHandlerFactory`
- [ ] Add translations to `lang/en/admin.php` and `lang/ar/admin.php`
- [ ] Add notification method to `OptimizedCompetitionNotificationService` (if needed)
- [ ] Write unit tests for the handler
- [ ] Write feature tests for the complete workflow
- [ ] Test cleanup functionality with factory
- [ ] Verify database constraints work correctly
- [ ] Update this documentation

## Cleanup System

The approval system includes an automatic cleanup mechanism to prevent database bloat and give users fresh opportunities.

### How It Works

- **Cleanup Period**: 24 hours
- **Frequency**: Every 6 hours via scheduler
- **Scope**: All approval statuses (pending, approved, rejected)
- **Purpose**: Give request senders second chances and admins fresh opportunities

### Benefits

1. **Prevents Database Bloat** - Automatically removes old approval records
2. **Fresh Opportunities** - Admins who rejected can be invited again after 24 hours
3. **Dynamic System** - Adapts to changing circumstances and preferences
4. **Better UX** - Request senders aren't stuck with old pending requests

### Implementation

```php
// Cleanup command (runs every 6 hours)
app(\App\Console\Commands::class)->cleanupExpiredApprovals();

// Manual cleanup
php artisan tinker --execute="app(\App\Console\Commands::class)->cleanupExpiredApprovals();"
```

### Testing Cleanup

```php
// Create test data with factory
AdminApproval::factory()->old()->pending()->count(5)->create();
AdminApproval::factory()->recent()->approved()->count(3)->create();

// Run cleanup
app(\App\Console\Commands::class)->cleanupExpiredApprovals();

// Verify results
AdminApproval::count(); // Should be 3 (recent ones remain)
```

### Business Logic

- **24-Hour Window**: Gives reasonable time for admins to respond
- **All Statuses**: Cleans pending, approved, and rejected records
- **Fresh Start**: Allows for changing circumstances and preferences
- **No Permanent Decisions**: Prevents stuck approval states

## Related Files

- `app/Enums/AdminApprovalTypeEnum.php` - Approval type definitions
- `app/Contracts/Approval/ApprovalHandlerInterface.php` - Handler contract
- `app/Factories/Approval/ApprovalHandlerFactory.php` - Handler factory
- `app/Services/Approval/ApprovalAssignmentService.php` - Main approval service
- `app/Services/Admin/AdminApprovalService.php` - Approval request management
- `app/Models/Admin/AdminApproval.php` - Approval model
- `app/Services/Notification/OptimizedCompetitionNotificationService.php` - Notification service
- `app/Console/Commands.php` - Cleanup command
- `routes/console.php` - Scheduler configuration
- `database/factories/Admin/AdminApprovalFactory.php` - Test data factory
- `lang/en/admin.php` - English translations
- `lang/ar/admin.php` - Arabic translations 