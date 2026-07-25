<?php

return [

    'enabled' => env('MCA_UPLOAD_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Default disk & directory
    |--------------------------------------------------------------------------
    |
    | Prefer a "web" disk that writes under public/ (shared hosting friendly).
    | Falls back to "public" when web is not configured.
    |
    */
    'disk' => env('MCA_UPLOAD_DISK', 'web'),

    'fallback_disk' => env('MCA_UPLOAD_FALLBACK_DISK', 'public'),

    'directory' => env('MCA_UPLOAD_DIRECTORY', 'uploads/mca'),

    'max_kb' => (int) env('MCA_UPLOAD_MAX_KB', 2048),

    /*
    |--------------------------------------------------------------------------
    | Default allowed MIME types (images)
    |--------------------------------------------------------------------------
    */
    'allowed_mimes' => [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'image/x-icon',
        'image/vnd.microsoft.icon',
    ],

    /*
    |--------------------------------------------------------------------------
    | SVG — disabled by default (XSS risk). Enable only when sanitized.
    |--------------------------------------------------------------------------
    */
    'allow_svg' => (bool) env('MCA_UPLOAD_ALLOW_SVG', false),

    'blocked_extensions' => [
        'php', 'phtml', 'phar', 'exe', 'bat', 'cmd', 'sh', 'js', 'html', 'htm', 'shtml',
    ],

    /*
    |--------------------------------------------------------------------------
    | Naming
    |--------------------------------------------------------------------------
    */
    'name_strategy' => env('MCA_UPLOAD_NAME_STRATEGY', 'ulid'), // ulid|uuid|uniqid

    /*
    |--------------------------------------------------------------------------
    | Presets (branding etc.)
    |--------------------------------------------------------------------------
    */
    'presets' => [
        'branding.favicon' => [
            'directory' => 'uploads/branding',
            'max_kb' => 1024,
            'prefix' => 'favicon',
            'allowed_mimes' => [
                'image/png',
                'image/x-icon',
                'image/vnd.microsoft.icon',
                'image/jpeg',
                'image/webp',
            ],
        ],
        'branding.light_logo' => [
            'directory' => 'uploads/branding',
            'max_kb' => 2048,
            'prefix' => 'light-logo',
        ],
        'branding.light_logo_sm' => [
            'directory' => 'uploads/branding',
            'max_kb' => 2048,
            'prefix' => 'light-logo-sm',
        ],
        'branding.dark_logo' => [
            'directory' => 'uploads/branding',
            'max_kb' => 2048,
            'prefix' => 'dark-logo',
        ],
        'branding.dark_logo_sm' => [
            'directory' => 'uploads/branding',
            'max_kb' => 2048,
            'prefix' => 'dark-logo-sm',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cloud Box HTTP driver (optional, phase 2)
    |--------------------------------------------------------------------------
    */
    'cloudbox' => [
        'enabled' => (bool) env('MCA_UPLOAD_CLOUDBOX_ENABLED', false),
        'base_url' => env('MCA_UPLOAD_CLOUDBOX_URL'),
        'token' => env('MCA_UPLOAD_CLOUDBOX_TOKEN'),
    ],

];
