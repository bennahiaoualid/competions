# Job Monitoring System Documentation

## Overview

The Job Monitoring System provides comprehensive tracking, monitoring, and retry capabilities for Laravel queue jobs. It includes duplicate detection, status tracking, and UI monitoring through PowerGrid.

## Architecture

### Core Components

1. **JobTrackingService** - Main service for job tracking operations
2. **BaseTrackableJob** - Base class for trackable jobs
3. **JobTracking Model** - Database model for job tracking records
4. **DuplicateJobChecker** - Prevents duplicate job execution
5. **JobTrackingStrategy** - Interface for different tracking strategies
6. **MonitoringController** - Web interface for job monitoring

### Job Tracking Flow

```
Job Creation → Trackable Job → JobTrackingService → Database Record → UI Monitoring
     ↓
Duplicate Check → Job Execution → Status Update → Retry (if needed)
```

## Features

### 1. Job Tracking
- **Automatic tracking** of job execution
- **Status monitoring** (pending, running, completed, failed)
- **Execution time tracking**
- **Error message capture**
- **Attempt counting**

### 2. Duplicate Prevention
- **Automatic duplicate detection**
- **Configurable duplicate checking**
- **Prevents redundant job execution**
- **Maintains system efficiency**

### 3. Retry Mechanism
- **Automatic retry for failed jobs**
- **Configurable retry attempts**
- **Exponential backoff support**
- **Manual retry capability**

### 4. UI Monitoring
- **PowerGrid integration**
- **Real-time status updates**
- **Bulk operations support**
- **Detailed job information**

## Usage

### Creating Trackable Jobs

```php
use App\Jobs\Base\BaseTrackableJob;

class YourJob extends BaseTrackableJob
{
    public function __construct(
        private array $data,
        private int $userId
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        // Your job logic here
        $this->updateStatus('completed');
    }

    // Required for job reconstruction
    public static function fromTrackingPayload(
        array $payload, 
        int $userId, 
        string $trackingId
    ): static {
        return new static($payload['data'], $userId);
    }
}
```

### Dispatching Trackable Jobs

```php
use App\Services\Monitoring\JobTrackingService;

class YourService
{
    public function __construct(
        private JobTrackingService $jobTrackingService
    ) {}

    public function processData(array $data): string
    {
        $job = new YourJob($data, Auth::id());
        return $this->jobTrackingService->dispatchWithTracking($job);
    }
}
```

### Monitoring Jobs

```php
// Get job status
$status = $this->jobTrackingService->getJobStatus($trackingId);

// Retry failed job
$this->jobTrackingService->retryFailedJob($trackingId);

// Delete job tracking
$this->jobTrackingService->deleteJob($trackingId);
```

## Database Schema

### JobTracking Table

```sql
CREATE TABLE job_trackings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id VARCHAR(255) NOT NULL,
    job_class VARCHAR(255) NOT NULL,
    job_type VARCHAR(100) NOT NULL,
    status ENUM('pending', 'running', 'completed', 'failed') DEFAULT 'pending',
    payload JSON NULL,
    result JSON NULL,
    error_message TEXT NULL,
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    failed_at TIMESTAMP NULL,
    user_id BIGINT UNSIGNED NULL,
    entity_type VARCHAR(255) NULL,
    entity_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_job_id (job_id),
    INDEX idx_status (status),
    INDEX idx_user_id (user_id),
    INDEX idx_entity (entity_type, entity_id)
);
```

## Configuration

### Job Tracking Strategy

```php
// config/app.php
'job_tracking_strategy' => env('JOB_TRACKING_STRATEGY', 'database'),
```

Available strategies:
- **database** - Store tracking in database (default)
- **memory** - Store tracking in memory (for testing)

### Duplicate Checking

```php
// In your job class
protected bool $checkDuplicates = true;
protected array $duplicateKeys = ['user_id', 'data_type'];
```

## UI Monitoring

### PowerGrid Integration

The system includes PowerGrid tables for monitoring:

- **Job Tracking List** - View all tracked jobs
- **Status filtering** - Filter by job status
- **Bulk operations** - Delete multiple jobs
- **Real-time updates** - Live status updates

### Accessing the UI

```php
// Route to job monitoring
Route::get('/admin/monitoring/jobs', [MonitoringController::class, 'jobTrackingList'])
    ->name('admin.monitoring.jobs');

// Retry job
Route::post('/admin/monitoring/jobs/{jobId}/retry', [MonitoringController::class, 'jobRetry'])
    ->name('admin.monitoring.jobs.retry');

// Delete job
Route::delete('/admin/monitoring/jobs/{jobId}', [MonitoringController::class, 'jobDelete'])
    ->name('admin.monitoring.jobs.delete');
```

## Testing

### Unit Tests

```php
class JobTrackingServiceTest extends TestCase
{
    public function test_dispatch_with_tracking(): void
    {
        $service = app(JobTrackingService::class);
        $job = new TestJob();
        
        $trackingId = $service->dispatchWithTracking($job);
        
        $this->assertNotNull($trackingId);
        $this->assertDatabaseHas('job_trackings', [
            'job_id' => $trackingId,
            'status' => 'pending'
        ]);
    }

    public function test_retry_failed_job(): void
    {
        $service = app(JobTrackingService::class);
        
        $result = $service->retryFailedJob($trackingId);
        
        $this->assertTrue($result);
    }
}
```

### Factory for Testing

```php
// Create test job tracking
JobTracking::factory()->create([
    'status' => 'failed',
    'attempts' => 1,
    'max_attempts' => 3
]);

// Create completed job
JobTracking::factory()->completed()->create();

// Create failed job
JobTracking::factory()->failed()->create();
```

## Best Practices

### 1. Job Design
- **Extend BaseTrackableJob** for automatic tracking
- **Implement fromTrackingPayload** for job reconstruction
- **Update status** during job execution
- **Handle errors gracefully**

### 2. Performance
- **Use duplicate checking** to prevent redundant jobs
- **Monitor job execution time** for optimization
- **Clean up old tracking records** periodically
- **Use appropriate queue connections**

### 3. Monitoring
- **Check job status regularly**
- **Monitor failed jobs** and retry patterns
- **Set up alerts** for critical job failures
- **Review job execution logs**

### 4. Error Handling
- **Capture detailed error messages**
- **Implement proper retry logic**
- **Log job execution details**
- **Handle job timeouts gracefully**

## Troubleshooting

### Common Issues

1. **Job not tracked** - Ensure job extends BaseTrackableJob
2. **Duplicate jobs** - Check duplicate checking configuration
3. **Retry not working** - Verify fromTrackingPayload implementation
4. **UI not updating** - Check PowerGrid configuration

### Debug Tips

```php
// Check job status
$status = app(JobTrackingService::class)->getJobStatus($trackingId);
dd($status);

// Check duplicate jobs
$duplicates = JobTracking::where('job_class', YourJob::class)
    ->where('status', 'pending')
    ->get();
dd($duplicates);

// Check job payload
$tracking = JobTracking::find($trackingId);
dd($tracking->payload);
```

## Related Files

- `app/Services/Monitoring/JobTrackingService.php` - Main service
- `app/Jobs/Base/BaseTrackableJob.php` - Base trackable job
- `app/Models/Monitoring/JobTracking.php` - Tracking model
- `app/Services/Monitoring/DuplicateJobChecker.php` - Duplicate checker
- `app/Http/Controllers/Monitoring/MonitoringController.php` - UI controller
- `app/Livewire/JobTrackingTable.php` - PowerGrid table
- `database/migrations/2025_06_25_140136_create_job_trackings_table.php` - Migration
- `database/factories/Monitoring/JobTrackingFactory.php` - Test factory

## Migration Guide

When adding job monitoring to existing jobs:

1. **Extend BaseTrackableJob** instead of Job
2. **Implement fromTrackingPayload** method
3. **Update job dispatch** to use JobTrackingService
4. **Add status updates** in job handle method
5. **Test job tracking** with factory
6. **Update UI** if needed

## Performance Considerations

- **Index optimization** - Ensure proper database indexes
- **Cleanup strategy** - Remove old tracking records
- **Queue optimization** - Use appropriate queue connections
- **Memory management** - Monitor memory usage for large jobs 