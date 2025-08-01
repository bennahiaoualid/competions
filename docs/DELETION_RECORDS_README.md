# Deletion Records System Documentation

## Overview

The Deletion Records System provides a comprehensive and safe approach to handling entity deletions with rollback capabilities. It uses a factory pattern to handle different entity types (admins, users, etc.) and includes job tracking for long-running deletion operations.

## Architecture

### Core Components

1. **DeletionRecordsService** - Main service for deletion orchestration
2. **DeletionRequest Model** - Database model for deletion requests
3. **RestoreHandlerFactory** - Factory for restore operations
4. **HardDeleteHandlerFactory** - Factory for hard delete operations
5. **DeletionRecordsController** - Web interface for deletion management
6. **DeletionRecordsTable** - PowerGrid table for UI monitoring

### Deletion Flow

```
Deletion Request → Validation → Handler Creation → Job Dispatch → Execution → Status Update
     ↓
Rollback Support → Error Handling → Logging → UI Update
```

## Features

### 1. Safe Deletion
- **Soft delete support** - Entities are soft-deleted first
- **Hard delete capability** - Permanent removal when needed
- **Rollback support** - Restore deleted entities
- **Validation checks** - Ensure safe deletion conditions

### 2. Generic Architecture
- **Factory pattern** - Handles different entity types
- **Extensible design** - Easy to add new entity types
- **Job tracking** - Monitor long-running deletions
- **Error handling** - Comprehensive error management

### 3. Entity Types Supported
- **Admin entities** - Admin deletion with ownership transfer
- **User entities** - User deletion with data cleanup
- **Custom entities** - Extensible for new entity types
- **Bulk operations** - Handle multiple entities

### 4. UI Monitoring
- **PowerGrid integration** for deletion monitoring
- **Real-time status updates**
- **Bulk operations** support
- **Detailed deletion information**

## Usage

### Creating a Deletion Request

```php
use App\Models\Monitoring\DeletionRequest;
use App\Models\Admin\Admin;

// Create deletion request for admin
$deletionRequest = DeletionRequest::create([
    'deletable_type' => Admin::class,
    'deletable_id' => $adminId,
    'requested_by' => Auth::id(),
    'reason' => 'Admin resigned',
    'status' => 'pending'
]);
```

### Performing Hard Delete

```php
use App\Services\Monitoring\DeletionRecordsService;

class YourController
{
    public function __construct(
        private DeletionRecordsService $deletionRecordsService
    ) {}

    public function deleteAdmin(Request $request, DeletionRequest $deletionRequest)
    {
        $success = $this->deletionRecordsService->hardDelete($deletionRequest, $request);
        
        if ($success) {
            $this->flasher->crudSuccess('deleted');
        } else {
            $this->flasher->crudFailure('deleted');
        }
        
        return redirect()->back();
    }
}
```

### Restoring Deleted Entity

```php
public function restoreEntity(DeletionRequest $deletionRequest)
{
    $success = $this->deletionRecordsService->restore($deletionRequest);
    
    if ($success) {
        $this->flasher->crudSuccess('restored');
    } else {
        $this->flasher->crudFailure('restored');
    }
    
    return redirect()->back();
}
```

## Database Schema

### DeletionRequest Table

```sql
CREATE TABLE deletion_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    deletable_type VARCHAR(255) NOT NULL,
    deletable_id BIGINT UNSIGNED NOT NULL,
    requested_by BIGINT UNSIGNED NOT NULL,
    reason TEXT NULL,
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    approved_by BIGINT UNSIGNED NULL,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_deletable (deletable_type, deletable_id),
    INDEX idx_status (status),
    INDEX idx_requested_by (requested_by),
    FOREIGN KEY (requested_by) REFERENCES admins(id),
    FOREIGN KEY (approved_by) REFERENCES admins(id)
);
```

## Handler System

### Creating a Handler

```php
use App\Contracts\Monitoring\RestoreHandlerInterface;
use App\Models\Monitoring\DeletionRequest;
use App\Models\Admin\Admin;

class AdminRestoreHandler implements RestoreHandlerInterface
{
    public function __construct(
        private Admin $admin,
        private DeletionRequest $deletionRequest,
        private Admin $approver
    ) {}

    public function restore(): bool
    {
        try {
            // Restore admin logic
            $this->admin->restore();
            
            // Update deletion request status
            $this->deletionRequest->update([
                'status' => 'restored',
                'approved_by' => $this->approver->id,
                'approved_at' => now()
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to restore admin', [
                'admin_id' => $this->admin->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
```

### Registering Handlers

```php
// In RestoreHandlerFactory
public function make($deletable, DeletionRequest $deletionRequest, Admin $approver): RestoreHandlerInterface
{
    return match ($deletionRequest->deletable_type) {
        Admin::class => new AdminRestoreHandler($deletable, $deletionRequest, $approver),
        User::class => new UserRestoreHandler($deletable, $deletionRequest, $approver),
        default => throw new \InvalidArgumentException('Unsupported entity type')
    };
}
```

## Configuration

### Job Tracking Integration

```php
// DeletionRecordsService constructor
public function __construct(
    private TransactionManagerInterface $transaction_manager,
    private JobTrackingService $jobTrackingService,
    private FlasherInterface $flasher,
    private RestoreHandlerFactory $restore_handler_factory,
    private HardDeleteHandlerFactory $hard_delete_handler_factory
) {}
```

### Entity Type Support

```php
// Supported entity types
$supportedTypes = [
    Admin::class,
    User::class,
    // Add more entity types here
];
```

## UI Monitoring

### PowerGrid Integration

The system includes PowerGrid tables for monitoring:

- **Deletion Requests List** - View all deletion requests
- **Status filtering** - Filter by request status
- **Bulk operations** - Approve/reject multiple requests
- **Real-time updates** - Live status updates

### Accessing the UI

```php
// Route to deletion records
Route::get('/admin/monitoring/deletion-records', [DeletionRecordsController::class, 'index'])
    ->name('admin.monitoring.deletion-records');

// Hard delete
Route::post('/admin/monitoring/deletion-records/{request}/hard-delete', [DeletionRecordsController::class, 'hardDelete'])
    ->name('admin.monitoring.deletion-records.hard-delete');

// Restore
Route::post('/admin/monitoring/deletion-records/{request}/restore', [DeletionRecordsController::class, 'restore'])
    ->name('admin.monitoring.deletion-records.restore');
```

## Testing

### Unit Tests

```php
class DeletionRecordsServiceTest extends TestCase
{
    public function test_hard_delete_admin(): void
    {
        $service = app(DeletionRecordsService::class);
        $deletionRequest = DeletionRequest::factory()->create([
            'deletable_type' => Admin::class,
            'deletable_id' => Admin::factory()->create()->id
        ]);
        
        $result = $service->hardDelete($deletionRequest, null);
        
        $this->assertTrue($result);
    }

    public function test_restore_admin(): void
    {
        $service = app(DeletionRecordsService::class);
        $deletionRequest = DeletionRequest::factory()->create([
            'deletable_type' => Admin::class,
            'deletable_id' => Admin::factory()->create()->id
        ]);
        
        $result = $service->restore($deletionRequest);
        
        $this->assertTrue($result);
    }
}
```

### Factory for Testing

```php
// Create deletion request
DeletionRequest::factory()->create([
    'deletable_type' => Admin::class,
    'deletable_id' => Admin::factory()->create()->id,
    'status' => 'pending'
]);

// Create approved request
DeletionRequest::factory()->approved()->create();

// Create completed request
DeletionRequest::factory()->completed()->create();
```

## Best Practices

### 1. Deletion Design
- **Always soft delete first** - Use soft deletes for safety
- **Validate deletion conditions** - Check dependencies
- **Handle rollback gracefully** - Ensure restore works
- **Log deletion activities** - Track all operations

### 2. Performance
- **Use job tracking** for long-running deletions
- **Batch operations** for multiple entities
- **Clean up old requests** periodically
- **Use appropriate indexes** for queries

### 3. Security
- **Validate permissions** before deletion
- **Check dependencies** to prevent orphaned data
- **Audit deletion activities** for compliance
- **Handle sensitive data** appropriately

### 4. Error Handling
- **Capture detailed error messages**
- **Implement proper rollback logic**
- **Log deletion execution details**
- **Handle deletion timeouts gracefully**

## Troubleshooting

### Common Issues

1. **Deletion not working** - Check entity type support
2. **Restore failing** - Verify soft delete implementation
3. **Job not tracking** - Check JobTrackingService integration
4. **UI not updating** - Check PowerGrid configuration

### Debug Tips

```php
// Check deletion request
$request = DeletionRequest::find($requestId);
dd($request->deletable);

// Check entity status
$entity = $request->deletable;
dd($entity->trashed()); // Check if soft deleted

// Check job status
$jobStatus = app(JobTrackingService::class)->getJobStatus($trackingId);
dd($jobStatus);
```

## Related Files

- `app/Services/Monitoring/DeletionRecordsService.php` - Main service
- `app/Models/Monitoring/DeletionRequest.php` - Request model
- `app/Factories/Monitoring/RestoreHandlerFactory.php` - Restore factory
- `app/Factories/Monitoring/HardDeleteHandlerFactory.php` - Delete factory
- `app/Http/Controllers/Monitoring/DeletionRecordsController.php` - UI controller
- `app/Livewire/DeletionRecordsTable.php` - PowerGrid table
- `database/migrations/2025_07_03_112958_create_deletion_requests_table.php` - Migration
- `database/factories/Monitoring/DeletionRequestFactory.php` - Test factory

## Migration Guide

When adding new entity types:

1. **Create handler classes** - Implement RestoreHandlerInterface and HardDeleteHandlerInterface
2. **Register in factories** - Add to RestoreHandlerFactory and HardDeleteHandlerFactory
3. **Add translations** - Update language files
4. **Write tests** - Test deletion and restore operations
5. **Update UI** - Add to PowerGrid table if needed
6. **Document** - Update this README

## Performance Considerations

- **Index optimization** - Ensure proper database indexes
- **Job tracking** - Use job tracking for long operations
- **Batch operations** - Handle multiple entities efficiently
- **Cleanup strategy** - Remove old deletion requests 