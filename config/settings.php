<?php

return [
    /*
    |--------------------------------------------------------------------------
    | System Settings Validation Rules
    |--------------------------------------------------------------------------
    |
    | This file contains validation rules for all system settings.
    | These rules are used to validate setting values when they are updated.
    |
    */

    'validation' => [
        /*
        |--------------------------------------------------------------------------
        | Payment Settings Validation
        |--------------------------------------------------------------------------
        */
        'max_daily_transactions' => [
            'type' => 'integer',
            'min' => 1,
            'max' => 10,
            'message' => 'settings.validation.max_daily_transactions'
        ],
        'min_competition_coins' => [
            'type' => 'integer',
            'min' => 100,
            'max' => 10000,
            'message' => 'settings.validation.min_competition_coins'
        ],
        'competition_gift' => [
            'type' => 'integer',
            'min' => 50,
            'max' => 5000,
            'message' => 'settings.validation.competition_gift'
        ],
        'max_daily_amount' => [
            'type' => 'integer',
            'min' => 1000,
            'max' => 100000,
            'message' => 'settings.validation.max_daily_amount'
        ],
        'min_transaction_amount' => [
            'type' => 'integer',
            'min' => 50,
            'max' => 500,
            'message' => 'settings.validation.min_transaction_amount'
        ],

        /*
        |--------------------------------------------------------------------------
        | System Settings Validation
        |--------------------------------------------------------------------------
        */
        'maintenance_mode' => [
            'type' => 'boolean',
            'message' => 'settings.validation.maintenance_mode'
        ],
        'max_file_upload_size' => [
            'type' => 'integer',
            'min' => 1048576, // 1MB
            'max' => 104857600, // 100MB
            'message' => 'settings.validation.max_file_upload_size'
        ],
        'session_timeout' => [
            'type' => 'integer',
            'min' => 15, // 15 minutes
            'max' => 1440, // 24 hours
            'message' => 'settings.validation.session_timeout'
        ],

        /*
        |--------------------------------------------------------------------------
        | Competition Settings Validation
        |--------------------------------------------------------------------------
        */
        'max_competition_duration' => [
            'type' => 'integer',
            'min' => 1,
            'max' => 365,
            'message' => 'settings.validation.max_competition_duration'
        ],
        'min_competition_participants' => [
            'type' => 'integer',
            'min' => 2,
            'max' => 1000,
            'message' => 'settings.validation.min_competition_participants'
        ],

        /*
        |--------------------------------------------------------------------------
        | AI Question Generation Settings Validation
        |--------------------------------------------------------------------------
        */
        'global_question_generating_cost' => [
            'type' => 'integer',
            'min' => 1,
            'max' => 100,
            'message' => 'settings.validation.global_question_generating_cost'
        ],
        'global_question_custom_difficulty_cost' => [
            'type' => 'integer',
            'min' => 0,
            'max' => 50,
            'message' => 'settings.validation.global_question_custom_difficulty_cost'
        ],
        'global_question_custom_subject_cost' => [
            'type' => 'integer',
            'min' => 0,
            'max' => 50,
            'message' => 'settings.validation.global_question_custom_subject_cost'
        ],
        'global_question_max_output_tokens' => [
            'type' => 'integer',
            'min' => 100,
            'max' => 2000,
            'message' => 'settings.validation.global_question_max_output_tokens'
        ],
        'global_question_premium_cost_percentage' => [
            'type' => 'integer',
            'min' => 1,
            'max' => 100,
            'message' => 'settings.validation.premium_question_cost_percentage'
        ],
        /*
        |--------------------------------------------------------------------------
        | Notification Settings Validation
        |--------------------------------------------------------------------------
        */
        'email_notifications_enabled' => [
            'type' => 'boolean',
            'message' => 'settings.validation.email_notifications_enabled'
        ],
        'push_notifications_enabled' => [
            'type' => 'boolean',
            'message' => 'settings.validation.push_notifications_enabled'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Values
    |--------------------------------------------------------------------------
    |
    | Default values for system settings when seeding the database.
    |
    */
    'defaults' => [
        // Payment Rules
        'max_daily_transactions' => 10,
        'min_competition_coins' => 500,
        'competition_gift' => 500,
        'max_daily_amount' => 10000,
        'min_transaction_amount' => 100,
        
        // System Configuration
        'maintenance_mode' => false,
        'max_file_upload_size' => 10485760, // 10MB
        'session_timeout' => 120, // 2 hours
        
        // Competition Settings
        'max_competition_duration' => 30,
        'min_competition_participants' => 5,
        
        // AI Question Generation Settings
        'global_question_generating_cost' => 10, // Base cost for generating a question
        'global_question_custom_difficulty_cost' => 10, // Additional cost for custom difficulty
        'global_question_custom_subject_cost' => 10, // Additional cost for custom subject
        'global_question_max_output_tokens' => 350, // Maximum tokens for AI response
        'global_question_premium_cost_percentage' => 50, // Premium question cost percentage
        // Notification Settings
        'email_notifications_enabled' => true,
        'push_notifications_enabled' => true
    ]
]; 