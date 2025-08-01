# Delayed Process System Documentation

## Overview

The Delayed Process System provides a robust mechanism for managing time-delayed operations with configurable retry logic. It's designed for operations that need to be executed after a specific time period, such as admin availability checks, ownership transfers, and other time-sensitive processes.

## Architecture

### Core Components

1. **DelayedProcessService** - Main service for process management
2. **DelayedProcess Model** - Database model with retry logic
3. **DelayedProcessCreationEvent** - Event-driven process creation
4. **DelayedProcessCreationListener** - Handles process creation events
5. **DelayedProcessController** - Web interface for process management
6. **DelayedProcessTable** - PowerGrid table for UI monitoring

### Process Flow

```
Process Request → Event Creation → Database Storage → Time Check → Execution → Cleanup
     ↓
Retry Logic → Status Update → Notification → UI Update
```

## Features

### 1. Time-Based Execution
- **Configurable check periods** (default: 24 hours)
- **Automatic retry logic** based on time intervals
- **Priority-based ordering** for process execution
- **Flexible scheduling** for different process types

### 2. Process Types
- **Admin Availability** - Check admin availability for roles
- **Ownership Transfer** - Handle admin ownership transfers
- **Custom Processes** - Extensible for new process types
- **Entity-Specific** - Target specific entities (admins, users, etc.)

### 3. Event-Driven Architecture
- **Event creation** for process initiation
- **Listener handling** for process storage
- **Automatic logging** of process activities
- **Error handling** with comprehensive logging

### 4. UI Monitoring
- **PowerGrid integration** for process monitoring
- **Real-time status updates**
- **Process management** (create, delete, retry)
- **Detailed process information**

## Usage

### Creating a Delayed Process

```php
use App\Services\ProcessManagement\DelayedProcessService;
use App\Enums\ProcessTypeEnum;

class YourService
{
    public function __construct(
        private DelayedProcessService $delayedProcessService
    ) {}

    public function scheduleProcess(int $adminId): void
    {
        $process = $this->delayedProcessService->storeDelayedProcess(
            processType: ProcessTypeEnum::ADMIN_AVAILABILITY,
            targetType: Admin::class,
            targetId: $adminId,
            initiatorId: Auth::id(),
            contextData: ['role' => 'auditor'],
            checkPeriodHours: 24
        );
    }
}
```

### Event-Driven Process Creation

```php
use App\Events\ProcessManagement\DelayedProcessCreationEvent;
use App\Enums\ProcessTypeEnum;

// Dispatch event for process creation
event(new DelayedProcessCreationEvent(
    processType: ProcessTypeEnum::ADMIN_AVAILABILITY,
    targetType: Admin::class,
    targetId: $adminId,
    initiatorId: Auth::id(),
    contextData: ['role' => 'auditor'],
    checkPeriodHours: 24
));
```

### Checking Process Status

```php
use App\Models\ProcessManagement\DelayedProcess;

// Find existing process
$process = DelayedProcess::byType(ProcessTypeEnum::ADMIN_AVAILABILITY)
    ->where('target_type', Admin::class)
    ->where('target_id', $adminId)
    ->first();

// Check if ready for retry
if ($process && $process->isReadyForRetry()) {
    // Execute process logic
    $this->executeProcess($process);
}
```

## Database Schema

### DelayedProcess Table

```sql
CREATE TABLE delayed_processes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    process_type VARCHAR(50) NOT NULL,
    target_type VARCHAR(50) NOT NULL,
    target_id BIGINT UNSIGNED NOT NULL,
    initiator_id BIGINT UNSIGNED NULL,
    context_data JSON NULL,
    check_period_hours INT DEFAULT 24,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_process_retry (process_type, target_type, target_id, created_at),
    INDEX idx_ready_for_retry (created_at, check_period_hours),
    UNIQUE KEY uk_delayed_process_unique (process_type, target_type, target_id),
    FOREIGN KEY (initiator_id) REFERENCES admins(id) ON DELETE SET NULL
);
```

## Process Types

### Available Process Types

```php
enum ProcessTypeEnum: string
{
    case ADMIN_AVAILABILITY = 'admin_availability';
    case OWNERSHIP_TRANSFER = 'ownership_transfer';
    case CUSTOM_PROCESS = 'custom_process';
}
```

### Adding New Process Types

```php
// 1. Add to enum
enum ProcessTypeEnum: string
{
    case ADMIN_AVAILABILITY = 'admin_availability';
    case OWNERSHIP_TRANSFER = 'ownership_transfer';
    case YOUR_NEW_TYPE = 'your_new_type';
}

// 2. Create handler (optional)
class YourProcessHandler
{
    public function handle(DelayedProcess $process): bool
    {
        // Your process logic here
        return true;
    }
}
```

## Configuration

### Check Period Configuration

```php
// Default check period (24 hours)
$process = $this->delayedProcessService->storeDelayedProcess(
    processType: ProcessTypeEnum::ADMIN_AVAILABILITY,
    targetType: Admin::class,
    targetId: $adminId,
    checkPeriodHours: 24 // Configurable
);
```

### Process Priority

```php
// Higher priority processes (shorter check periods)
$process = $this->delayedProcessService->storeDelayedProcess(
    processType: ProcessTypeEnum::URGENT_PROCESS,
    targetType: Admin::class,
    targetId: $adminId,
    checkPeriodHours: 1 // 1 hour for urgent processes
);
```

## UI Monitoring

### PowerGrid Integration

The system includes PowerGrid tables for monitoring:

- **Process List** - View all delayed processes
- **Status filtering** - Filter by process type and status
- **Process management** - Create, delete, retry processes
- **Real-time updates** - Live status updates

### Accessing the UI

```php
// Route to process monitoring
Route::get('/admin/delayed-processes', [DelayedProcessController::class, 'index'])
    ->name('admin.delayed-processes.index');

// Create process
Route::post('/admin/delayed-processes', [DelayedProcessController::class, 'store'])
    ->name('admin.delayed-processes.store');

// Delete process
Route::delete('/admin/delayed-processes/{process}', [DelayedProcessController::class, 'destroy'])
    ->name('admin.delayed-processes.destroy');
```

## Testing

### Unit Tests

```php
class DelayedProcessServiceTest extends TestCase
{
    public function test_store_delayed_process(): void
    {
        $service = app(DelayedProcessService::class);
        
        $process = $service->storeDelayedProcess(
            processType: ProcessTypeEnum::ADMIN_AVAILABILITY,
            targetType: Admin::class,
            targetId: 1,
            initiatorId: Auth::id(),
            checkPeriodHours: 24
        );
        
        $this->assertNotNull($process);
        $this->assertEquals(ProcessTypeEnum::ADMIN_AVAILABILITY, $process->process_type);
    }

    public function test_find_existing_process(): void
    {
        $service = app(DelayedProcessService::class);
        
        $existing = $service->findExistingProcess(
            ProcessTypeEnum::ADMIN_AVAILABILITY,
            Admin::class,
            1
        );
        
        $this->assertNull($existing); // No existing process
    }
}
```

### Factory for Testing

```php
// Create test process
DelayedProcess::factory()->create([
    'process_type' => ProcessTypeEnum::ADMIN_AVAILABILITY,
    'target_type' => Admin::class,
    'target_id' => 1,
    'check_period_hours' => 24
]);

// Create ready for retry process
DelayedProcess::factory()->readyForRetry()->create();

// Create process with specific type
DelayedProcess::factory()->forType(ProcessTypeEnum::OWNERSHIP_TRANSFER)->create();
```

## Best Practices

### 1. Process Design
- **Use appropriate check periods** for different process types
- **Include context data** for process execution
- **Handle process failures gracefully**
- **Log process activities** for debugging

### 2. Performance
- **Use database indexes** for efficient queries
- **Clean up completed processes** periodically
- **Monitor process execution time**
- **Use appropriate queue connections**

### 3. Monitoring
- **Check process status regularly**
- **Monitor process execution patterns**
- **Set up alerts** for failed processes
- **Review process logs** for optimization

### 4. Error Handling
- **Capture detailed error messages**
- **Implement proper retry logic**
- **Log process execution details**
- **Handle process timeouts gracefully**

## Troubleshooting

### Common Issues

1. **Process not executing** - Check check_period_hours configuration
2. **Duplicate processes** - Verify unique constraint
3. **Process not found** - Check target_type and target_id
4. **UI not updating** - Check PowerGrid configuration

### Debug Tips

```php
// Check process status
$process = DelayedProcess::find($processId);
dd($process->isReadyForRetry());

// Check ready processes
$ready = DelayedProcess::readyForRetry()->get();
dd($ready);

// Check process by type
$processes = DelayedProcess::byType(ProcessTypeEnum::ADMIN_AVAILABILITY)->get();
dd($processes);
```

## Related Files

- `app/Services/ProcessManagement/DelayedProcessService.php` - Main service
- `app/Models/ProcessManagement/DelayedProcess.php` - Process model
- `app/Events/ProcessManagement/DelayedProcessCreationEvent.php` - Creation event
- `app/Listeners/ProcessManagement/DelayedProcessCreationListener.php` - Event listener
- `app/Http/Controllers/Admin/DelayedProcessController.php` - UI controller
- `app/Livewire/DelayedProcessTable.php` - PowerGrid table
- `database/migrations/2025_07_12_181233_create_delayed_processes_table.php` - Migration
- `database/factories/ProcessManagement/DelayedProcessFactory.php` - Test factory
- `lang/en/delayed_process.php` - English translations
- `lang/ar/delayed_process.php` - Arabic translations

## Migration Guide

When adding new process types:

1. **Add to ProcessTypeEnum** - Define new process type
2. **Create handler** (optional) - Implement process logic
3. **Add translations** - Update language files
4. **Write tests** - Test process creation and execution
5. **Update UI** - Add to PowerGrid table if needed
6. **Document** - Update this README

## Performance Considerations

- **Index optimization** - Ensure proper database indexes
- **Cleanup strategy** - Remove completed processes
- **Queue optimization** - Use appropriate queue connections
- **Memory management** - Monitor memory usage for large processes 