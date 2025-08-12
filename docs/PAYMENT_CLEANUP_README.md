# Payment Cleanup System

This document explains how the automated payment cleanup system works and how to configure it.

## Overview

The Payment Cleanup System automatically manages payment data lifecycle by:
- Removing expired payment transactions based on configurable rules
- Cleaning up old proof images from disk only (preserving database records for legal compliance)
- Auto-expiring coin offers that have passed their end date
- Maintaining audit trails for all cleanup operations

## How It Works

### 1. Scheduled Commands

The system runs automatically through Laravel's task scheduler:

```php
// routes/console.php
// Auto-expire coin offers daily at 1 AM
Schedule::call(function () {
    app(\App\Console\Commands::class)->expireCoinOffers();
})->dailyAt('01:00');

// Clean up expired payment data daily at 2 AM
Schedule::call(function () {
    app(\App\Console\Commands::class)->cleanupPaymentData();
})->dailyAt('02:00');



// Archive old payment audit logs daily at 4 AM
Schedule::call(function () {
    app(\App\Console\Commands::class)->archivePaymentAuditLogs();
})->dailyAt('04:00');
```

### 2. Image-Only Cleanup Strategy

**IMPORTANT**: The system now only removes proof images from disk storage, NOT from the database. This ensures legal compliance by preserving payment records while freeing up storage space.

**Key Benefits**:
- **Legal Compliance**: Database records are preserved as proof of payment existence
- **Storage Optimization**: Old images are removed from disk to save space
- **Audit Trail**: Complete history of what was cleaned and when
- **Dispute Resolution**: Proof that images existed and were removed per policy

### 3. Cleanup Rules

```php
// config/payment.php
'cleanup' => [
    'rules' => [
        'approved' => [
            'keep_days' => 365,    // Keep approved payments for 1 year
            'cleanup_images' => 1095  // Keep proof images for 3 years (1095 days)
        ],
        'rejected' => [
            'keep_days' => 30,     // Keep rejected payments for 30 days
            'cleanup_images' => 90  // Keep proof images for 90 days
        ],
        'cancelled' => [
            'keep_days' => 7,      // Keep cancelled payments for 7 days
            'cleanup_images' => 60  // Keep proof images for 60 days
        ],
        'pending' => [
            'keep_days' => null,   // NEVER auto-clean pending payments
            'cleanup_images' => false // Keep proof images for pending payments
        ]
    ]
]
```

### 3. Protection Rules

The system protects important data from cleanup:

```php
'protection_rules' => [
    'pending_review' => [
        'keep_payment' => true,    // Never clean payments with pending reviews
        'keep_images' => true,     // Keep proof images for pending reviews
        'reason' => 'Payment has pending review request'
    ],
    'recent_activity' => [
        'keep_payment' => true,    // Keep payments with recent activity
        'keep_images' => true,     // Keep proof images for recent activity
        'activity_days' => 7,      // Days to consider "recent"
        'reason' => 'Payment has recent activity'
    ]
]
```

## Configuration

### Environment Variables

You can customize the cleanup behavior using environment variables:

```env
# Cleanup timing
PAYMENT_CLEANUP_DAILY_TIME=02:00

# Cleanup rules
PAYMENT_CLEANUP_APPROVED_DAYS=365
PAYMENT_CLEANUP_REJECTED_DAYS=30
PAYMENT_CLEANUP_CANCELLED_DAYS=7
PAYMENT_CLEANUP_APPROVED_IMAGES_DAYS=1095
PAYMENT_CLEANUP_REJECTED_IMAGES_DAYS=90
PAYMENT_CLEANUP_CANCELLED_IMAGES_DAYS=60
PAYMENT_CLEANUP_ACTIVITY_DAYS=7

# Batch processing
PAYMENT_CLEANUP_BATCH_SIZE=1000

# Logging
PAYMENT_CLEANUP_LOGGING=true
PAYMENT_CLEANUP_LOG_LEVEL=info
PAYMENT_CLEANUP_DETAILED_LOGGING=false

# Proof storage
PAYMENT_PROOFS_DISK=local
PAYMENT_PROOFS_PATH=transactions

# Audit archiving
PAYMENT_AUDIT_ARCHIVE_DAYS=365
PAYMENT_AUDIT_ARCHIVE_BATCH_SIZE=1000
PAYMENT_AUDIT_ARCHIVE_TIME=04:00
```

### Configuration File

The main configuration is in `config/payment.php`:

```php
return [
    'cleanup' => [
        'rules' => [...],
        'protection_rules' => [...],
        'timing' => [...],
        'batch_size' => env('PAYMENT_CLEANUP_BATCH_SIZE', 1000),
        'logging' => [...]
    ],
    'audit' => [...],
    'proofs' => [...]
];
```

## Manual Execution

You can run cleanup commands manually for testing or immediate cleanup:

```bash
# Expire coin offers
php artisan tinker --execute="app(\App\Console\Commands::class)->expireCoinOffers();"

# Clean up payment data
php artisan tinker --execute="app(\App\Console\Commands::class)->cleanupPaymentData();"

# Create backup
php artisan tinker --execute="app(\App\Console\Commands::class)->backupPaymentProofs();"

# Archive audit logs
php artisan tinker --execute="app(\App\Console\Commands::class)->archivePaymentAuditLogs();"
```

## Monitoring

### Logs

All cleanup operations are logged with detailed information:

```php
// Example log entries
Log::info('Starting payment cleanup process');
Log::info('Found 15 expired approved payments older than 365 days');
Log::info('Deleted 15 expired approved payments in batch');
Log::info('Payment cleanup process completed successfully', [
    'expired_payments' => 15,
    'expired_proof_images' => 8,
    'backup_created' => false,
    'duration_seconds' => 45
]);
```

### Statistics

You can get cleanup statistics programmatically:

```php
use App\Services\Payment\PaymentCleanupService;

$cleanupService = app(PaymentCleanupService::class);
$stats = $cleanupService->getCleanupStats();

// Returns:
[
    'approved' => [
        'total_count' => 150,
        'expired_count' => 15,
        'keep_days' => 365,
        'cutoff_date' => '2024-01-15'
    ],
    'rejected' => [
        'total_count' => 25,
        'expired_count' => 8,
        'keep_days' => 30,
        'cutoff_date' => '2024-12-20'
    ],
    'proof_images' => [
        'total_count' => 175,
        'storage_size' => 52428800 // 50 MB
    ]
]
```

## Security Features

### Data Protection

- **Pending payments are never auto-cleaned**
- **Payments with pending reviews are protected**
- **Recent activity prevents premature cleanup**
- **Complete audit trail for all operations**

### File Security

- **Proof images stored in private storage**
- **Backup compression for efficient storage**
- **Secure file deletion with error handling**
- **IP-based access control (when implemented)**

## Performance

### Batch Processing

- **Configurable batch size** (default: 1000 records)
- **Memory-efficient chunking** for large datasets
- **Transaction-based operations** for data integrity
- **Progress logging** for long-running operations**

### Storage Optimization

- **Automatic image compression** (JPEG, 85% quality)
- **Size limits** (max 1200x1200px for proofs)
- **Format standardization** (all images converted to JPEG)
- **Efficient backup creation** with compression

## Troubleshooting

### Common Issues

1. **Cleanup not running**: Check Laravel scheduler is running
2. **Permission errors**: Verify storage disk permissions
3. **Memory issues**: Reduce batch size in configuration
4. **Backup failures**: Check disk space and permissions

### Debug Mode

Enable detailed logging for troubleshooting:

```env
PAYMENT_CLEANUP_DETAILED_LOGGING=true
PAYMENT_CLEANUP_LOG_LEVEL=debug
```

### Manual Testing

Test individual components:

```php
// Test cleanup service
$cleanupService = app(PaymentCleanupService::class);
$results = $cleanupService->cleanupPaymentData();

// Test backup creation
$backupCreated = $cleanupService->backupPaymentProofs();

// Get statistics
$stats = $cleanupService->getCleanupStats();
```

## Future Enhancements

### Planned Features

- **Audit logs archiving** to separate table
- **Payment statistics cache refresh**
- **Email notifications** for cleanup operations
- **Cleanup dashboard** for monitoring
- **Custom cleanup schedules** per payment type

### Extensibility

The system is designed to be easily extended:

- **New cleanup rules** can be added to configuration
- **Additional protection rules** can be implemented
- **Custom cleanup strategies** can be added to the service
- **New scheduled commands** can be registered

## Best Practices

### Configuration

1. **Start with conservative retention periods**
2. **Monitor cleanup logs** for unexpected behavior
3. **Test cleanup rules** in development first
4. **Backup before major cleanup operations**

### Monitoring

1. **Check logs daily** for cleanup operations
2. **Monitor storage usage** for proof images
3. **Verify backup creation** weekly
4. **Review cleanup statistics** monthly

### Maintenance

1. **Review cleanup rules** quarterly
2. **Adjust retention periods** based on business needs
3. **Clean up old backup files** periodically
4. **Update configuration** as requirements change 