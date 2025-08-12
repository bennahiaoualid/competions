<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Image Processing Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for image processing
    | including validation rules, storage settings, and processing options.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | File Size Limits
    |--------------------------------------------------------------------------
    |
    | Maximum file size in KB (default: 10MB)
    |
    */
    'max_size' => env('IMAGE_MAX_SIZE', 10240), // 10MB in KB

    /*
    |--------------------------------------------------------------------------
    | Allowed MIME Types
    |--------------------------------------------------------------------------
    |
    | Array of allowed image file extensions
    |
    */
    'allowed_mimes' => [
        'jpeg',
        'jpg', 
        'png',
        'gif',
        'webp'
    ],

    /*
    |--------------------------------------------------------------------------
    | Dimension Limits
    |--------------------------------------------------------------------------
    |
    | Minimum and maximum width/height in pixels
    |
    */
    'min_width' => env('IMAGE_MIN_WIDTH', 100),
    'max_width' => env('IMAGE_MAX_WIDTH', 2000),
    'min_height' => env('IMAGE_MIN_HEIGHT', 100),
    'max_height' => env('IMAGE_MAX_HEIGHT', 2000),

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Default disk and path settings
    |
    */
    'default_disk' => env('IMAGE_DISK', 'local'),
    'default_path' => env('IMAGE_PATH', 'uploads'),
    
    /*
    |--------------------------------------------------------------------------
    | Image Type Configuration
    |--------------------------------------------------------------------------
    |
    | Define which types of images should be stored publicly vs privately
    |
    */
    'public_types' => [
        'competition' => [
            'disk' => 'public',
            'path' => 'competitions',
        ],
        'system' => [
            'disk' => 'public', 
            'path' => 'system',
        ],
        'public_content' => [
            'disk' => 'public',
            'path' => 'public',
        ],
    ],
    
    'private_types' => [
        'profile' => [
            'disk' => 'local',
            'path' => 'profiles',
        ],
        'document' => [
            'disk' => 'local',
            'path' => 'documents',
        ],
        'transaction' => [
            'disk' => 'local',
            'path' => 'transactions',
            'max_width' => 1200,
            'max_height' => 1200,
            'quality' => 85,
        ],
        'admin' => [
            'disk' => 'local',
            'path' => 'admin',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Processing Options
    |--------------------------------------------------------------------------
    |
    | Quality and format settings for processed images
    |
    */
    'quality' => env('IMAGE_QUALITY', 80),
    'format' => env('IMAGE_FORMAT', 'jpeg'),

    /*
    |--------------------------------------------------------------------------
    | Thumbnail Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for thumbnail generation
    |
    */
    'generate_thumbnails' => env('IMAGE_GENERATE_THUMBNAILS', false),
    'thumbnail_sizes' => [
        'small' => [150, 150],
        'medium' => [300, 300],
        'large' => [600, 600],
    ],

    /*
    |--------------------------------------------------------------------------
    | Memory Management
    |--------------------------------------------------------------------------
    |
    | Memory safety settings for large image processing
    |
    */
    'memory_safety_margin' => env('IMAGE_MEMORY_SAFETY_MARGIN', 0.8), // 80% of memory limit

    /*
    |--------------------------------------------------------------------------
    | Watermark Settings
    |--------------------------------------------------------------------------
    |
    | Default watermark configuration
    |
    */
    'watermark' => [
        'enabled' => env('IMAGE_WATERMARK_ENABLED', false),
        'path' => env('IMAGE_WATERMARK_PATH', 'watermarks/default.png'),
        'position' => env('IMAGE_WATERMARK_POSITION', 'bottom-right'),
        'opacity' => env('IMAGE_WATERMARK_OPACITY', 0.7),
        'size_percentage' => env('IMAGE_WATERMARK_SIZE_PERCENTAGE', 0.2), // 20% of image size
    ],

]; 