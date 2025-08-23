<?php

return [
    'default_provider' => env('LLM_DEFAULT_PROVIDER', 'openai'),
    
    'providers' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'enabled' => env('OPENAI_ENABLED', true),
        ],
        'anthropic' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1'),
            'enabled' => env('ANTHROPIC_ENABLED', true),
        ],
        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
            'enabled' => env('GEMINI_ENABLED', true),
        ],
    ],

    'models' => [
        'openai' => [
            'default' => env('OPENAI_DEFAULT_MODEL', 'gpt-3.5-turbo'),
            'available' => [
                'gpt-3.5-turbo',
                'gpt-4',
                'gpt-4-turbo',
                'gpt-4o',
                'gpt-4o-mini'
            ],
        ],
        'anthropic' => [
            'default' => env('ANTHROPIC_DEFAULT_MODEL', 'claude-3-sonnet'),
            'available' => [
                'claude-3-haiku',
                'claude-3-sonnet',
                'claude-3-opus',
                'claude-3.5-sonnet',
                'claude-3.5-haiku'
            ],
        ],
        'gemini' => [
            'default' => env('GEMINI_DEFAULT_MODEL', 'gemini-1.5-flash'),
            'available' => [
                'gemini-1.5-flash',
                'gemini-1.5-pro',
                'gemini-1.5-flash-latest',
                'gemini-1.5-pro-latest',
                'gemini-pro',
                'gemini-pro-vision'
            ],
        ],
    ],

    'rate_limits' => [
        'requests_per_minute' => env('LLM_RATE_LIMIT_PER_MINUTE', 60),
        'requests_per_hour' => env('LLM_RATE_LIMIT_PER_HOUR', 1000),
    ],

    'timeouts' => [
        'request_timeout' => env('LLM_REQUEST_TIMEOUT', 30),
        'connection_timeout' => env('LLM_CONNECTION_TIMEOUT', 10),
    ],
]; 