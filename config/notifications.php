<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Notification Batch Settings
    |--------------------------------------------------------------------------
    |
    | Configure batch processing thresholds and settings for notifications.
    |
    */

    'batch' => [
        /*
        |--------------------------------------------------------------------------
        | Batch Threshold
        |--------------------------------------------------------------------------
        |
        | Number of users above which batch processing is used instead of
        | individual notifications. Set to 0 to always use batch processing.
        |
        */
        'threshold' => env('NOTIFICATION_BATCH_THRESHOLD', 10),

        /*
        |--------------------------------------------------------------------------
        | Chunk Size
        |--------------------------------------------------------------------------
        |
        | Number of notifications to process in each chunk for very large
        | user bases to prevent memory issues.
        |
        */
        'chunk_size' => env('NOTIFICATION_CHUNK_SIZE', 1000),

        /*
        |--------------------------------------------------------------------------
        | Queue Names
        |--------------------------------------------------------------------------
        |
        | Dedicated queue names for different notification types.
        |
        */
        'queues' => [
            'notifications' => env('NOTIFICATION_QUEUE', 'notifications'),
            'broadcasting' => env('BROADCAST_QUEUE', 'broadcasting'),
            'emails' => env('EMAIL_QUEUE', 'emails'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Real-time Events
    |--------------------------------------------------------------------------
    |
    | Events that should be broadcast in real-time to users.
    |
    */
    'real_time_events' => [
        'activated',
        'level_activated',
        'level_finished',
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Settings for optimizing notification performance.
    |
    */
    'performance' => [
        /*
        |--------------------------------------------------------------------------
        | Job Timeout
        |--------------------------------------------------------------------------
        |
        | Maximum time (in seconds) a notification job can run before timing out.
        |
        */
        'timeout' => env('NOTIFICATION_TIMEOUT', 300),

        /*
        |--------------------------------------------------------------------------
        | Job Retries
        |--------------------------------------------------------------------------
        |
        | Number of times to retry failed notification jobs.
        |
        */
        'retries' => env('NOTIFICATION_RETRIES', 3),

        /*
        |--------------------------------------------------------------------------
        | Job Backoff
        |--------------------------------------------------------------------------
        |
        | Number of seconds to wait between retries.
        |
        */
        'backoff' => env('NOTIFICATION_BACKOFF', 60),
    ],

]; 