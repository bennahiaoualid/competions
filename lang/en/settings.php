<?php

return [
    'title' => 'System Settings',
    'subtitle' => 'Manage global system configuration and rules',
    
    // Categories
    'categories' => [
        'payment' => [
            'title' => 'Payment Rules',
            'description' => 'Manage payment rules and transaction limits',
        ],
        'system' => [
            'title' => 'System Configuration',
            'description' => 'Manage system configuration and settings',
        ],
        'notifications' => [
            'title' => 'Notification Settings',
            'description' => 'Manage notification settings and preferences',
        ],
        'service_global_question' => [
            'title' => 'AI Question Generation',
            'description' => 'Manage AI question generation costs and settings',
        ],
        'service_ai_auditing' => [
            'title' => 'AI Auditing',
            'description' => 'Manage AI auditing costs and settings',
        ],
    ],
    
    // Payment Rules
    'payment' => [
        'max_daily_transactions' => [
            'name' => 'Maximum Daily Transactions',
            'description' => 'Maximum number of transactions a user can make per day',
            'message' => 'Maximum {value} transactions per day',
            'unit' => 'transactions',
            'help' => 'Set the daily limit for payment transactions per user'
        ],
        'min_competition_coins' => [
            'name' => 'Minimum Competition Coins',
            'description' => 'Minimum coins required to create a competition',
            'message' => 'Minimum {value} coins required',
            'unit' => 'coins',
            'help' => 'Set the minimum coin requirement for competition creation'
        ],
        'competition_gift' => [
            'name' => 'Competition Gift',
            'description' => 'Platform coins gift added to each competition',
            'message' => 'Platform gift: {value} coins',
            'unit' => 'coins',
            'help' => 'Set the platform contribution to competition rewards'
        ],
         // Competition Settings
        'second_place_winner_percentage' => [
            'name' => 'Second Place Winner Percentage',
            'description' => 'Percentage of competition gift coins for 2nd place winner',
            'message' => 'Second place gets {value}% of competition gift',
            'unit' => '%',
            'help' => 'Set the percentage of competition gift coins that 2nd place winner receives'
        ],
        'third_place_winner_percentage' => [
            'name' => 'Third Place Winner Percentage',
            'description' => 'Percentage of competition gift coins for 3rd place winner',
            'message' => 'Third place gets {value}% of competition gift',
            'unit' => '%',
            'help' => 'Set the percentage of competition gift coins that 3rd place winner receives'
        ],
    ],
    
    
    // System Configuration
    'system' => [
        'maintenance_mode' => [
            'name' => 'Maintenance Mode',
            'description' => 'Enable or disable maintenance mode for the system',
            'message' => 'Maintenance mode is {value}',
            'unit' => 'status',
            'help' => 'When enabled, only administrators can access the system'
        ],
        'max_file_upload_size' => [
            'name' => 'Maximum File Upload Size',
            'description' => 'Maximum allowed file size for uploads',
            'message' => 'Maximum file size: {value} bytes',
            'unit' => 'bytes',
            'help' => 'Set the maximum file size limit for all uploads'
        ],
        'session_timeout' => [
            'name' => 'Session Timeout',
            'description' => 'User session timeout in minutes',
            'message' => 'Session timeout: {value} minutes',
            'unit' => 'minutes',
            'help' => 'Set how long user sessions remain active'
        ],
    ],
    
    // Notification Settings
    'notifications' => [
        'email_enabled' => [
            'name' => 'Email Notifications',
            'description' => 'Enable or disable email notifications',
            'message' => 'Email notifications are {value}',
            'unit' => 'status',
            'help' => 'Control whether email notifications are sent'
        ],
        'push_enabled' => [
            'name' => 'Push Notifications',
            'description' => 'Enable or disable push notifications',
            'message' => 'Push notifications are {value}',
            'unit' => 'status',
            'help' => 'Control whether push notifications are sent'
        ],
    ],
    
    // AI Question Generation Settings
    'ai' => [
        'service_global_question' =>[
            'generating_cost' => [
                'name' => 'Question Generation Base Cost',
                'description' => 'Base cost in coins for generating an AI question',
                'message' => 'Base cost: {value} coins',
                'unit' => 'coins',
                'help' => 'Set the base cost for AI question generation'
            ],
            'custom_difficulty_cost' => [
                'name' => 'Custom Difficulty Cost',
                'description' => 'Additional cost for selecting a specific difficulty level',
                'message' => 'Custom difficulty cost: {value} coins',
                'unit' => 'coins',
                'help' => 'Additional cost when user selects a specific difficulty instead of random'
            ],
            'custom_subject_cost' => [
                'name' => 'Custom Subject Cost',
                'description' => 'Additional cost for selecting a specific subject',
                'message' => 'Custom subject cost: {value} coins',
                'unit' => 'coins',
                'help' => 'Additional cost when user selects a specific subject instead of random'
            ],
            'max_output_tokens' => [
                'name' => 'Maximum Output Tokens',
                'description' => 'Maximum number of tokens for AI question generation',
                'message' => 'Maximum tokens: {value}',
                'unit' => 'tokens',
                'help' => 'Set the maximum token limit for AI responses'
            ],
            'premium_cost_percentage' => [
                'name' => 'Premium Question Cost Percentage',
                'description' => 'if genrating cost is 100 coins, and premium cost percentage is 50%, then premium cost will be 50 coins',
                'message' => 'Premium cost percentage: {value}%',
                'unit' => '%',
                'help' => 'Set the percentage of the base cost for premium questions'
            ],
            'llm_provider' => [
                'name' => 'LLM Provider',
                'description' => 'AI provider for question generation service',
                'message' => 'Using {value} provider',
                'unit' => 'provider',
                'help' => 'Select the AI provider for question generation'
            ],
            'model' => [
                'name' => 'AI Model',
                'description' => 'AI model for question generation service',
                'message' => 'Using {value} model',
                'unit' => 'model',
                'help' => 'Select the AI model for question generation'
            ]
        ],
        'service_ai_auditing' => [
            'cost_per_response' => [
                'name' => 'Cost Per Response',
                'description' => 'Cost in coins for auditing each response',
                'message' => 'Cost per response: {value} coins',
                'unit' => 'coins',
                'help' => 'Set the cost for auditing each competition response'
            ],
            'max_response_auditing_at_one_batch' => [
                'name' => 'Max Responses Per Batch',
                'description' => 'Maximum number of responses to audit in one batch',
                'message' => 'Max {value} responses per batch',
                'unit' => 'responses',
                'help' => 'Maximum responses to process in a single AI auditing batch'
            ],
            'llm_provider' => [
                'name' => 'LLM Provider',
                'description' => 'AI provider for auditing service',
                'message' => 'Using {value} provider',
                'unit' => 'provider',
                'help' => 'Select the AI provider for response auditing'
            ],
            'model' => [
                'name' => 'AI Model',
                'description' => 'AI model for auditing service',
                'message' => 'Using {value} model',
                'unit' => 'model',
                'help' => 'Select the AI model for response auditing'
            ]
        ],
    ],
    
    // Common
    'common' => [
        'save' => 'Save Settings',
        'cancel' => 'Cancel',
        'edit' => 'Edit Setting',
        'update' => 'Update Setting',
        'delete' => 'Delete Setting',
        'confirm_delete' => 'Are you sure you want to delete this setting?',
        'setting_updated' => 'Setting updated successfully',
        'setting_deleted' => 'Setting deleted successfully',
        'validation_error' => 'Please check the input values',
        'no_settings' => 'No settings found',
        'loading' => 'Loading settings...',
        'refresh' => 'Refresh Settings',
        'export' => 'Export Settings',
        'import' => 'Import Settings',
        'current_value' => 'Current Value',
        'unit' => 'Unit',
        'help' => 'Help',
        'new_value' => 'New Value',
        'disabled' => 'Disabled',
        'enabled' => 'Enabled',
    ],
    
    // Form Labels
    'form' => [
        'setting_key' => 'Setting Key',
        'setting_value' => 'Setting Value',
        'setting_trans_key' => 'Translation Key',
        'category' => 'Category',
        'description' => 'Description',
        'validation_rules' => 'Validation Rules',
        'is_active' => 'Active',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
    ],
    
    // Validation Messages
    'validation' => [
        'setting_key_required' => 'Setting key is required',
        'setting_value_required' => 'Setting value is required',
        'setting_trans_key_required' => 'Translation key is required',
        'category_required' => 'Category is required',
        'invalid_value' => 'Invalid value for this setting',
        'value_out_of_range' => 'Value is out of allowed range',
        'duplicate_key' => 'Setting key already exists',

        'max_daily_transactions' => 'Daily transaction limit must be between 1 and 10',
        'min_competition_coins' => 'Minimum competition coins must be between 100 and 10,000',
        'competition_gift' => 'Competition gift must be between 50 and 5,000',
        'second_place_winner_percentage' => 'Second place winner percentage must be between 0 and 100',
        'third_place_winner_percentage' => 'Third place winner percentage must be between 0 and 100',
        'maintenance_mode' => 'Maintenance mode must be either true or false',
        'max_file_upload_size' => 'Maximum file upload size must be between 10485760 and 104857600',
        'session_timeout' => 'Session timeout must be between 1 and 1440',
        'email_notifications_enabled' => 'Email notifications must be either true or false',
        'global_question_llm_provider' => 'LLM provider must be one of: openai, anthropic, gemini',
        'global_question_model' => 'AI model is required',
        'ai_auditing_cost_per_response' => 'Cost per response must be 0 or greater',
        'ai_auditing_max_response_auditing_at_one_batch' => 'Max responses per batch must be between 50 and 100',
        'ai_auditing_llm_provider' => 'LLM provider must be one of: openai, anthropic, gemini',
        'ai_auditing_model' => 'AI model is required',
    ],
    
    // Help Text
    'help' => [
        'setting_key' => 'Unique identifier for the setting (e.g., max_daily_transactions)',
        'setting_value' => 'Current value of the setting',
        'setting_trans_key' => 'Translation key for displaying the setting name and description',
        'category' => 'Grouping category for the setting',
        'description' => 'Detailed description of what this setting controls',
    ],
]; 