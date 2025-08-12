<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payment Cleanup Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for the payment cleanup system,
    | including rules for when to clean up different types of payment data
    | and how to protect important records.
    |
    */

    'cleanup' => [
        /*
        |--------------------------------------------------------------------------
        | Cleanup Rules
        |--------------------------------------------------------------------------
        |
        | Defines how long to keep different types of payment data and
        | when to clean up proof images.
        |
        */
        'rules' => [
            'approved' => [
                'keep_days' => env('PAYMENT_CLEANUP_APPROVED_DAYS', 365),    // Keep approved payments for 1 year
                'cleanup_images' => env('PAYMENT_CLEANUP_APPROVED_IMAGES_DAYS', 1095)  // Keep proof images for 3 years (1095 days)
            ],
            'rejected' => [
                'keep_days' => env('PAYMENT_CLEANUP_REJECTED_DAYS', 30),     // Keep rejected payments for 30 days
                'cleanup_images' => env('PAYMENT_CLEANUP_REJECTED_IMAGES_DAYS', 90) // Keep proof images for 90 days
            ],
            'cancelled' => [
                'keep_days' => env('PAYMENT_CLEANUP_CANCELLED_DAYS', 7),      // Keep cancelled payments for 7 days
                'cleanup_images' => env('PAYMENT_CLEANUP_CANCELLED_IMAGES_DAYS', 60) // Keep proof images for 60 days
            ],
            'pending' => [
                'keep_days' => null,   // NEVER auto-clean pending payments
                'cleanup_images' => false // Keep proof images for pending payments
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Cleanup Protection Rules
        |--------------------------------------------------------------------------
        |
        | Defines conditions that prevent automatic cleanup of payment data.
        |
        */
        'protection_rules' => [
            'pending_review' => [
                'keep_payment' => true,    // Never clean payments with pending reviews
                'keep_images' => true,     // Keep proof images for pending reviews
                'reason' => 'Payment has pending review request'
            ],
            'recent_activity' => [
                'keep_payment' => true,    // Keep payments with recent activity
                'keep_images' => true,     // Keep proof images for recent activity
                'activity_days' => env('PAYMENT_CLEANUP_ACTIVITY_DAYS', 7), // Days to consider "recent"
                'reason' => 'Payment has recent activity'
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Cleanup Timing
        |--------------------------------------------------------------------------
        |
        | Defines when cleanup operations should run.
        |
        */
        'timing' => [
            'daily_cleanup' => env('PAYMENT_CLEANUP_DAILY_TIME', '02:00'),    // Daily cleanup time
        ],

        /*
        |--------------------------------------------------------------------------
        | Batch Processing
        |--------------------------------------------------------------------------
        |
        | Defines how many records to process in each batch to avoid
        | memory issues and long-running transactions.
        |
        */
        'batch_size' => env('PAYMENT_CLEANUP_BATCH_SIZE', 1000),

        /*
        |--------------------------------------------------------------------------
        | Logging
        |--------------------------------------------------------------------------
        |
        | Defines logging behavior for cleanup operations.
        |
        */
        'logging' => [
            'enabled' => env('PAYMENT_CLEANUP_LOGGING', true),
            'level' => env('PAYMENT_CLEANUP_LOG_LEVEL', 'info'),
            'detailed' => env('PAYMENT_CLEANUP_DETAILED_LOGGING', false), // Log individual record details
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Audit Archive Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for archiving old payment audit logs.
    |
    */
    'audit' => [
        'archive_after_days' => env('PAYMENT_AUDIT_ARCHIVE_DAYS', 365),
        'batch_size' => env('PAYMENT_AUDIT_ARCHIVE_BATCH_SIZE', 1000),
        'archive_time' => env('PAYMENT_AUDIT_ARCHIVE_TIME', '04:00'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Proof Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for payment proof image storage and cleanup.
    |
    */
    'proofs' => [
        'storage_disk' => env('PAYMENT_PROOFS_DISK', 'local'),
        'storage_path' => env('PAYMENT_PROOFS_PATH', 'transactions'),
    ],


]; 